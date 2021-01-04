<?php

namespace App\Http\Middleware;

use App\ApiAllowed;
use App\Helpers\ApiDataHelper;
use App\Role;
use Closure;
use JWTAuth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(ApiDataHelper::getEnvironment() == ApiDataHelper::Environment["DEVELOPMENT"]){
            // Access view_api_allowed in Production Database for Development Environment
            $apiAllowed = ApiDataHelper::getApi($request->route()->uri, $request->route()->methods[0]);
        } else {
            // Access view_api_allowed for Production Environment
            $apiAllowed = ApiAllowed::where('action_url', $request->route()->uri)->where('action_method', $request->route()->methods[0])->first();
        }
        $userRole   = JWTAuth::parseToken()->authenticate()->role;

        if($apiAllowed != null){
            if($userRole == Role::ROLE["ADMIN"] && $apiAllowed->admin_allowed == ApiAllowed::ALLOWED_STATUS["ALLOWED"]){
                return $next($request);
            } else if($userRole == Role::ROLE["TUTOR"] && $apiAllowed->tutor_allowed == ApiAllowed::ALLOWED_STATUS["ALLOWED"]){
                return $next($request);
            } else if($userRole == Role::ROLE["STUDENT"] && $apiAllowed->student_allowed == ApiAllowed::ALLOWED_STATUS["ALLOWED"]){
                return $next($request);
            } else if($userRole == Role::ROLE["PUBLISHER"] && $apiAllowed->publisher_allowed == ApiAllowed::ALLOWED_STATUS["ALLOWED"]){
                return $next($request);
            } else if($userRole == Role::ROLE["SCHOOL"] && $apiAllowed->school_allowed == ApiAllowed::ALLOWED_STATUS["ALLOWED"]){
                return $next($request);
            } else if($userRole == Role::ROLE["MARKETING"] && $apiAllowed->marketing_allowed == ApiAllowed::ALLOWED_STATUS["ALLOWED"]){
                return $next($request);
            } else if($userRole == Role::ROLE["COMPANY"] && $apiAllowed->company_allowed == ApiAllowed::ALLOWED_STATUS["ALLOWED"]){
                return $next($request);
            } else {
                return response()->json([
                    'status'    => 'Failed',
                    'message'   => 'Forbidden Access'], 403);
            }
        } else {
            return response()->json([
                'status'    => 'Failed',
                'message'   => 'API is not registered'], 404);
        }
    }
}
