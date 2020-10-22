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
use App\TutorDetail;
class UserController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            JWTAuth::factory()->setTTL(14400);
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'status'    => 'failed',
                    'error'     => 'invalid_credentials',
                    'message'   => 'Email or Password is Wrong'], 400);
            }
        } catch (JWTException $e) {
            return response()->json([
                'status'    => 'failed',
                'error'     => 'could_not_create_token',
                'message'   => 'Cant create authentication, please try again'], 500);
        }

        return response()->json([
            'status'    => 'success',
            'token'     => $token,
            'message'   => 'Loggin is successfuly',
            'logged'    => 'true' ], 200);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'birth_date' => 'required|date',
            'photo' => 'file',
            'contact' => 'required|string|max:20',
            'company_id' => 'integer|max:20',
            'address' => 'required|string',

        ]);

        if($validator->fails()){
            // return response()->json($validator->errors()->toJson(), 400);
            return response()->json([
                'status'    =>'failed',
                'error'     =>$validator->errors()
            ],400);
        }
        $message = "Upload";
        try {
            $user = User::create([
                'name'          => $request->get('name'),
                'email'         => $request->get('email'),
                'password'      => Hash::make($request->get('password')),
                'birth_date'    => $request->get('birth_date'),
                'role'          => "student",
                'contact'       => $request->get('contact'),
                'company_id'    => $request->get('company_id'),
                'address'       => $request->get('address'),
            ]);
            try{
                $photo = $request->file('photo');
                $tujuan_upload = 'temp';
                $photo_name = $user->id.'_'.$photo->getClientOriginalName().'_'.Str::random(3).'.'.$photo->getClientOriginalExtension();
                $photo->move($tujuan_upload,$photo_name);
                $user->photo = $photo_name;
                $user->save();
                    $message = "Upload Success";
            }catch(\throwable $e){
                    $message = "Upload Success no image";
            }
            $token = JWTAuth::fromUser($user);
        } catch (\Throwable $th) {
            $user       = 'no user';
            $token      = 'no token';
            $message    = 'Failed To Create User';
            return response()->json(compact('user','token','message'),500);
        }



        return response()->json(compact('user','token','message'),201);
    }

    public function registerTutor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'birth_date' => 'required|date',
            'photo' => 'file',
            'contact' => 'required|string|max:20',
            'company_id' => 'integer|max:20',
            'address' => 'required|string',

        ]);

        if($validator->fails()){
            // return response()->json($validator->errors()->toJson(), 400);
            return response()->json([
                'status'    =>'failed',
                'error'     =>$validator->errors()
            ],400);
        }
        $message = "Upload";
        try {
            $user = User::create([
                'name'          => $request->get('name'),
                'email'         => $request->get('email'),
                'password'      => Hash::make($request->get('password')),
                'birth_date'    => $request->get('birth_date'),
                'role'          => "tutor",
                'contact'       => $request->get('contact'),
                'company_id'    => $request->get('company_id'),
                'address'       => $request->get('address'),
            ]);

            try{
                $photo = $request->file('photo');
                $tujuan_upload = 'temp';
                $photo_name = $user->id.'_'.$photo->getClientOriginalName().'_'.Str::random(3).'.'.$photo->getClientOriginalExtension();
                $photo->move($tujuan_upload,$photo_name);
                $user->photo = $photo_name;

                $message = "Upload Success";
            }catch(\throwable $e){
                $message = "Upload Success no image";
            }
            $user->save();

            $detail = new TutorDetail();
            $detail->user_id    = $user->id;
            $detail->status     = 'unverified';
            $detail->biography  = '-';

            $detail->save();

            $token = JWTAuth::fromUser($user);
        } catch (\Throwable $th) {
            $user       = 'no user';
            $token      = 'no token';
            $message    = 'Failed To Create User';
            $th         = $th;
            return response()->json(compact('user','token','message', 'th'),500);
        }

        return response()->json(compact('user','token','message'),201);
    }

    public function uploadPhoto(Request $request){
        $validator = Validator::make($request->all(), [
            'photo' => 'required|file',
        ]);

        if($validator->fails()){
            // return response()->json($validator->errors()->toJson(), 400);
            return response()->json([
                'status'    =>'failed validate',
                'error'     =>'No Photo Uploaded'
            ],400);
        }
        try {
            $userDetail = UserController::getAuthenticatedUserVariable();
            $user           = User::findOrFail($userDetail->id);
            $photo = $request->file('photo');
            $tujuan_upload = 'temp';
            $photo_name = $user->id.'_'.$photo->getClientOriginalName().'_'.Str::random(3).'.'.$photo->getClientOriginalExtension();
            $photo->move($tujuan_upload,$photo_name);
            $user->photo = $photo_name;
            $user->save();
            return response()->json([
                'status'    =>'success',
                'message'   =>'Yours Photo Uploaded'
            ],201);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>'failed validate',
                'error'     =>'No Photo Uploaded'
            ],400);
        }
    }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'string|email|max:255',
            'password' => 'string|min:6',
            'birth_date' => 'date',
            'photo' => 'file',
            'contact' => 'string|max:20',
            'company_id' => 'integer|max:20',
            'address' => 'string',

        ]);

        if($validator->fails()){
            // return response()->json($validator->errors()->toJson(), 400);
            return response()->json([
                'status'    =>'failed validate',
                'error'     =>$validator->errors()
            ],400);
        }
        $message = "Update";
        $status = "Success";
        try {
            $userDetail = UserController::getAuthenticatedUserVariable();
            $user = User::findOrFail($userDetail->id);
            if ($request->input('name')) {
                $user->name = $request->input('name');
            }if ($request->input('email')) {
                $user->email    = $request->input('email');
                $user->status   = 'unverified';
            }if ($request->input('password')){
                $user->password = Hash::make($request->input('password'));
            }if ($request->input('birth_date')) {
                $user->birth_date = $request->input('birth_date');
            }if ($request->input('contact')){
                $user->contact = $request->input('contact');
            }if ($request->input('company_id')) {
                $user->company_id = $request->input('company_id');
            }if ($request->input('address')) {
                $user->address = $request->input('address');
            }
            $message = "Update Success";
            $user->save();
            return response()->json(compact('user','status','message'),201);
        } catch (\Throwable $th) {
            $status     = 'Failed';
            $message    = 'Update is Failed';
            $error      = $th;
            return response()->json(compact('error','status','message'),201);
        }
    }

    public function updateById(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'string|email|max:255',
            'password' => 'string|min:6',
            'birth_date' => 'date',
            'photo' => 'file',
            'contact' => 'string|max:20',
            'company_id' => 'integer|max:20',
            'address' => 'string',

        ]);

        if($validator->fails()){
            // return response()->json($validator->errors()->toJson(), 400);
            return response()->json([
                'status'    =>'failed validate',
                'error'     =>$validator->errors()
            ],400);
        }
        $message = "Update";
        try {
            $user = User::findOrFail($id);
            if ($request->input('name')) {
                $user->name = $request->input('name');
            }if ($request->input('email')) {
                $user->email    = $request->input('email');
                $user->status   = 'unverified';
            }if ($request->input('password')){
                $user->password = Hash::make($request->get('password'));
            }if ($request->input('birth_date')) {
                $user->birth_date = $request->input('birth_date');
            }if ($request->input('contact')){
                $user->contact = $request->input('contact');
            }if ($request->input('company_id')) {
                $user->company_id = $request->input('company_id');
            }
            if ($request->input('address')) {
                $user->address = $request->input('address');
            }

            $user->save();

            return response()->json([
                'status'    => 'success',
                'message'   => "Success update user",
                'user'      => $user
            ],200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'    => 'failed',
                'message'   => "Failed to update user",
                'user'      => $user,
                'data'      => $th->getMessage()
            ],400);
        }
    }
    public function updateAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'string|email|max:255',
            'password' => 'string|min:6',
            'birth_date' => 'date',
            'photo' => 'file',
            'contact' => 'string|max:20',
            'company_id' => 'integer|max:20',
            'address' => 'string',

        ]);

        if($validator->fails()){
            // return response()->json($validator->errors()->toJson(), 400);
            return response()->json([
                'status'    =>'failed validate',
                'error'     =>$validator->errors()
            ],400);
        }
        $message = "Update";
        try {
            $userDetail = UserController::getAuthenticatedUserVariable();
            $user = User::findOrFail($userDetail->id);
            if ($request->input('name')) {
                $user->name = $request->input('name');
            }if ($request->input('email')) {
                $user->email    = $request->input('email');
                $user->status   = 'unverified';
            }if ($request->input('password')){
                $user->password = Hash::make($request->get('password'));
            }if ($request->input('birth_date')) {
                $user->birth_date = $request->input('birth_date');
            }if ($request->input('contact')){
                $user->contact = $request->input('contact');
            }if ($request->input('company_id')) {
                $user->company_id = $request->input('company_id');
            }
            $user->address = $request->get('address');
            $message = "Update Success";
            $user->save();
        } catch (\Throwable $th) {
            $status      = 'Failed';
            $message    = 'Update is Failed';
        }



        return response()->json(compact('user','status','message'),201);
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

            if($user->role == User::ROLE["TUTOR"]){
                $userId = $user->id;
                $tutor = User::where('id', $userId)
                        ->with(array(
                            'detail',
                            'tutorSubject'=>function($query){
                                $query->leftJoin('subject', 'subject.id', '=', 'tutor_subject.subject_id');
                            }, 'rating'=>function($query){
                                $query->selectRaw('tutor_id,AVG(rate) average')
                                ->groupBy('tutor_id');
                            }
                            , 'tutorDoc'=>function($query) use ($userId){
                                $query->where(function($q) use ($userId) {
                                    $q->whereIn('id', $q->selectRaw('MAX(id)')->where('tutor_id', $userId)->groupBy('type'));
                                });
                            }
                            ))->first();

                return response()->json([
                    'status'    => 'Success',
                    'token'     => $expires_at,
                    'user'      => $user,
                    'tutor'     => $tutor
                ], 200);
            } else {
                return response()->json([
                    'status'    => 'Success',
                    'token'     => $expires_at,
                    'user'      => $user
                ], 200);
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
        return $user;
    }

    public function forgetPassword(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255'
        ]);
        $password = Str::random(6);
        $pw =  Hash::make($password);
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
        Mail::send([], [], function ($message) use ($request, $pw,$password)
        {
            $message->subject('Contoh Otp');
            $message->to($request->email);
            $message->setBody('<p> Hi!! </p><h1>password anda</h1><br/><h1><b>'.$password.'</b></h1>','text/html');

        });
        }catch(\throwable $e){
            return response()->json([
                'status'=> 'failed sending email'],403);
        }
        return response()->json([
            'status' => 'success',
            'message'=> 'password has been changed']);
    }

    public function logout(){
            $token = JWTAuth::getToken();
            JWTAuth::setToken($token)->invalidate();
            return response()->json([
                'status'    =>  'success',
                'message'   =>  'Token now is invalidated'
            ]);


    }

    public function getAllStudent(Request $request){
        $paginate = 10;
        if($request->get('paginate')){
            $paginate = $request->get('paginate');
        }
        if($request->get('search')){
            $querySearch = $request->get('search');
            $data   =   User::where('role','Student')
                    ->where(function ($where) use ($querySearch){
                        $where->where('name','LIKE','%'.$querySearch.'%');
                    })->paginate($paginate);
            return $data;
        }
        $data   =   User::where('role','student')
                          ->paginate($paginate);
        return $data;
    }
    public function getStudent($id){
        try {
            //code...
            $data   =   User::findOrFail($id);
            return $data;
        } catch (\Throwable $th) {
            //throw $th;
            return "fail";
        }
    }

    public function updateBalance(Request $request)
    {
        $message = "Update";
        $status = "Success";
        try {
            $userDetail = UserController::getAuthenticatedUserVariable();
            $user = User::findOrFail($userDetail->id);
            $user->balance = $request->input('balance');
            $message = "Update Balance Success";
            $user->save();
        } catch (\Throwable $th) {
            $status      = 'Failed';
            $message    = 'Update is Failed';
        }

        return response()->json(compact('user','status','message'),201);
    }

    public function updateFirebaseToken(Request $request)
    {
        $message = "Update Firebase Token Succeed";
        $status = "Success";
        try {
            $userDetail = UserController::getAuthenticatedUserVariable();
            $user = User::findOrFail($userDetail->id);
            $user->firebase_token = $request->input('firebase_token');
            $message = "Update Firebase Token Success";
            $user->save();
        } catch (\Throwable $th) {
            $status      = 'Failed';
            $message    = 'Update is Failed';
            $th = $th;
        }

        return response()->json(compact('user','status','message'),201);
    }

    public function destroy($id)
    {
        try {
            // $user = User::findOrFail($id);

            // $user->deleted_at = date("Y-m-d");
            // $user->save();

            $user = User::where("id", $id)->first();

            if ($user->role == "tutor") {
                $user->room_vc()->delete();
                $user->room_chat()->delete();
                $user->history_vc()->delete();
            }

            $delete = $user->delete();

            if($delete){
                return response([
                    "status"	=> "success",
                    "message"   => "Success delete user"
                ]);
            } else {
                return response([
                    "status"    => "failed",
                    "message"   => "Failed to delete data"
                ]);
            }

        } catch (\Throwable $th) {
            return response([
                "status"	=> "success",
                "message"   => "failed to delete user"
            ]);
        }
    }
}
