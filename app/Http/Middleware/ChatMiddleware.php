<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;
use Exception;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use App\RoomChat;

class ChatMiddleware
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
            $roomkey    = $request->route('roomkey');
            $room       = RoomChat::where('room_key',$roomkey)->first();
            $user       = JWTAuth::parseToken()->authenticate();
            if ($user->id == $room->user_id) {
                return $next($request);
            }
            if ($user->id == $room->tutor_id) {
                return $next($request);
            }else{
                return response()->json([
                    'status'    => 'Room is not Invalid',
                    'message'   => 'Please enter yout valid room'],401);
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
    }
}
