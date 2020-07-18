<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Mail;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            JWTAuth::factory()->setTTL(14400);
            if (! $token = JWTAuth::attempt($credentials,)) {
                return response()->json([
                    'error'     => 'invalid_credentials',
                    'message'   => 'Email or Password is Wrong'], 400);
            }
        } catch (JWTException $e) {
            return response()->json([
                'error'     => 'could_not_create_token',
                'message'   => 'Cant create authentication, please try again'], 500);
        }

        return response()->json([
            'status'    => 'logged in',
            'token'     => $token ], 500);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'birth_date' => 'required|date',
            'photo' => 'required|string|max:255',
            'role' => 'in:student,parent,tutor,admin',
            'contact' => 'required|string|max:20',
            'company_id' => 'required|integer|max:20',
            'address' => 'required|string',

        ]);

        if($validator->fails()){
            // return response()->json($validator->errors()->toJson(), 400);
            return response()->json([
                'status'    =>'failed validate',
                'error'     =>$validator->errors()
            ],400);
        }

        $user = User::create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
            'birth_date' => $request->get('birth_date'),
            'photo' => $request->get('photo'),
            'role' => $request->get('role'),
            'contact' => $request->get('contact'),
            'company_id' => $request->get('company_id'),
            'address' => $request->get('address'),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json(compact('user','token'),201);
    }

    public function getAuthenticatedUser()
    {
        try {
            
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json([
                    'status'=> 'failed login',
                    'token' => 'user_not_found'], 404);
            }
            $payload = JWTAuth::parseToken()->getPayload();
            $expires_at = date('d M Y h:i', $payload->get('exp')); 

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

            return response()->json([
                'status'=> 'failed login',
                'token' => 'token_expired'],403);

        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

            return response()->json([
                'status'=> 'failed login',
                'token' => 'token_invalid'],403);

        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json([
                'status'=> 'failed login',
                'token' => 'token_absent'],403);

        }
        
        return response()->json([
            'status'=> 'Success',
            'token' => $expires_at,
            'user'  => $user],200);
    }
    public function getAuthenticatedUserVariable()
    {
        try {
            
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json([
                    'status'=> 'failed login',
                    'token' => 'user_not_found'], 404);
            }
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

            return response()->json([
                'status'=> 'failed login',
                'token' => 'token_expired'],403);

        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

            return response()->json([
                'status'=> 'failed login',
                'token' => 'token_invalid'],403);

        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json([
                'status'=> 'failed login',
                'token' => 'token_absent'],403);

        }
        
    }

    public function forgetPassword(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255'
        ]);
        $pw =  Hash::make(Str::random(6));
        if($validator->fails()){
            return response()->json([
                'status'    =>'failed validate',
                'error'     =>$validator->errors()
            ],400);
        }
        try{
            $user = User::where('email',$request->email)->first();
            $user->password = $pw;
            $user->save();
        }catch(\throwable $e){
            return response()->json([
                'status'    => 'failed reset',
                'message'   => 'user not found'],404);
        }
        try{
        Mail::send([], [], function ($message) use ($request, $pw)
        {
            $message->subject('Contoh Otp');
            $message->to($request->email);
            $message->setBody('<p> Hi!! </p><h1>password anda</h1><br/><h1><b>'.$pw.'</b></h1>','text/html');

        });
        }catch(\throwable $e){
            return response()->json([
                'status'=> 'failed reset'],403);
        }
        return response()->json([
            'status' => 'success',
            'message'=> 'password has been changed']);
    }
}