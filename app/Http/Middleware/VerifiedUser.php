<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;
use Exception;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class VerifiedUser
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
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if($user->status=='unverified'){
                return response()->json([
                    'status' => 'failed',
                    'message'   => 'User Aleready Verified'],401);     
            }
        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                return response()->json([
                    'status' => 'Token is Invalid',
                    'message'   => 'Login to get the token'],401);
            }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                return response()->json([
                    'status'    => 'Token is Expired',
                    'message'   => 'Please re-login to get token'],401);
            }else{
                return response()->json([
                    'status'    => 'Authorization Token not found',
                    'message'   => 'Login to get the token'],404);
            }
        }
        return $next($request);
    }
}
