<?php

namespace App\Http\Controllers;

use App\AppVersion;
use App\Information;
use App\Notification;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Mail;
use Illuminate\Support\Str;
use App\TutorDetail;
use App\Helpers\GoogleCloudStorageHelper;
use App\Otp;
use View;
use Google_Client;
use App\Helpers\LogApps;
use App\Helpers\ResponseHelper;
use App\Role;
use Facade\FlareClient\Http\Response;
use FCM;

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

        $user = User::where('email', $request->get('email'))->first();
        $dataLog = [
            "USER" => $user,
            "USER_IP" => $request->ip()
        ];

        LogApps::login($dataLog);

        return response()->json([
            'status'    => 'Success',
            'token'     => $token,
            'message'   => 'Logged in successfully',
            'logged'    => 'true',
            'role'      => (int)$user->role
        ], 200);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'birth_date' => 'required|date',
            'contact' => 'required|string|max:20',
            'address' => 'required|string',
            'jenjang' => 'required|integer|max:20'
        ]);

        if($validator->fails()){
            // return response()->json($validator->errors()->toJson(), 400);
            return response()->json([
                'status'    =>'Failed',
                'error'     =>$validator->errors()
            ],400);
        }

        try {
            $user = User::create([
                'name'          => $request->get('name'),
                'email'         => $request->get('email'),
                'password'      => Hash::make($request->get('password')),
                'birth_date'    => $request->get('birth_date'),
                'role'          => Role::ROLE["STUDENT"],
                'contact'       => $request->get('contact'),
                'address'       => $request->get('address'),
                'jenjang'       => $request->get('jenjang')
            ]);

            //Add user to Firebase Authentication
            $userProperties = [
                "email" => $request->get('email'),
                "password"  => $request->get('password')
            ];

            $auth = app('firebase.auth');
            $auth->createUser($userProperties);
            //End Add user to Firebase Authentication

            //Add user collection to firebase realtime database
            $firebaseUser = $auth->getUserByEmail($request->get('email'));
            $userFirebaseUid = $firebaseUser->uid;

            $database = app('firebase.database');

            $userData = [
                "id" => $user->id,
                "email" => $user->email,
                "password" => $user->password,
                "last_online" => now(),
                "online" => false
            ];

            $database->getReference("users/".$userFirebaseUid."/")->set($userData);
            //End of Add user collection to firebase realtime database

            return ResponseHelper::response(
                "Berhasil mendaftarkan akun, silahkan login",
                null,
                200,
                "Success"
            );

        } catch (\Throwable $th) {

            return ResponseHelper::response(
                "Gagal mendaftarkan akun, silahkan coba lagi".$th->getMessage(),
                null,
                400,
                "Failed"
            );
        }
    }

    public function registerTutor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'birth_date' => 'required|date',
            'contact' => 'required|string|max:20',
            'address' => 'required|string',
        ]);

        if($validator->fails()){
            return response()->json([
                'status'    =>'failed',
                'error'     =>$validator->errors()
            ],400);
        }

        try {

            $user = User::create([
                'name'          => $request->get('name'),
                'email'         => $request->get('email'),
                'password'      => Hash::make($request->get('password')),
                'birth_date'    => $request->get('birth_date'),
                'role'          => Role::ROLE["TUTOR"],
                'contact'       => $request->get('contact'),
                'address'       => $request->get('address'),
            ]);

            //Add user to Firebase Authentication
            $userProperties = [
                "email" => $request->get('email'),
                "password"  => $request->get('password')
            ];

            $auth = app('firebase.auth');
            $auth->createUser($userProperties);
            //End Add user to Firebase Authentication

            $user->save();

            $detail = new TutorDetail();
            $detail->user_id    = $user->id;
            $detail->status     = 'unverified';
            $detail->biography  = '-';

            $detail->save();

            //Add user collection to firebase realtime database
            $firebaseUser = $auth->getUserByEmail($request->get('email'));
            $userFirebaseUid = $firebaseUser->uid;

            $database = app('firebase.database');

            $userData = [
                "id" => $user->id,
                "email" => $user->email,
                "password" => $user->password,
                "last_online" => now(),
                "online" => false
            ];

            $database->getReference("users/".$userFirebaseUid."/")->set($userData);
            //End of Add user collection to firebase realtime database

            return ResponseHelper::response(
                "Berhasil mendaftarkan akun, silahkan login",
                null,
                200,
                "Success"
            );

        } catch (\Throwable $th) {
            return ResponseHelper::response(
                "Gagal mendaftarkan akun, silahkan coba lagi",
                null,
                400,
                "Success"
            );
        }
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
            // CloudKilatHelper::delete(CloudKilatHelper::getEnvironment().'/photos/user'.$user->photo);
            GoogleCloudStorageHelper::delete('/photos/user'.$user->photo);
            // $user->photo    = CloudKilatHelper::put($request->file('photo'), '/photos/user', 'image', $user->id);
            $user->photo    = GoogleCloudStorageHelper::put($request->file('photo'), '/photos/user', 'image', $user->id);

            $user->save();
            return response()->json([
                'status'    =>'success',
                'message'   =>'Yours Photo Uploaded',
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
            'contact' => 'string|max:20',
            'company_id' => 'integer|max:20',
            'address' => 'string',
            'jenjang' => 'integer'
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
            $user       = User::findOrFail($userDetail->id);
            $beforeData = User::findOrFail($userDetail->id);

            //Get firebase auth data
            $auth = app('firebase.auth');
            $userFirebase = $auth->getUserByEmail($user->email);
            $userFirebaseUid = $userFirebase->uid;

            if ($request->input('name')) {
                $user->name = $request->input('name');
            }
            if ($request->input('email')) {
                $user->email    = $request->input('email');
                $user->status   = 'unverified';
            }
            if ($request->input('password')){
                $user->password = Hash::make($request->input('password'));
            }
            if ($request->input('birth_date')) {
                $user->birth_date = $request->input('birth_date');
            }
            if ($request->input('contact')){
                $user->contact = $request->input('contact');
            }
            if ($request->input('company_id')) {
                $user->company_id = $request->input('company_id');
            }
            if ($request->input('address')) {
                $user->address = $request->input('address');
            }
            if ($request->input('jenjang')) {
                $user->jenjang = $request->input('jenjang');
            }

            $user->save();

            //Update firebase auth data
            if ($request->input("email")) {
                $auth->changeUserEmail($userFirebaseUid, $request->input("email"));
            }

            if ($request->input("password")) {
                $auth->changeUserPassword($userFirebaseUid, $request->input("password"));
            }
            //End of Update firebase auth data

            $dataLog = [
                "USER"      => $user,
                "USER_IP"   => $request->ip(),
                "BEFORE"    => $beforeData,
                "AFTER"     => $user
            ];

            if($request->input('password')){
                LogApps::editUser($dataLog, LogApps::UPDATE_USER_TYPE["RESET_PASSWORD"]);
            } else {
                LogApps::editUser($dataLog, LogApps::UPDATE_USER_TYPE["UPDATE_PROFILE"]);
            }

            return response()->json(compact('user','status','message'),201);
        } catch (\Exception $e) {
            return ResponseHelper::response(
                "Gagal mengedit profil",
                null,
                400,
                "Failed"
            );
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
            'address' => 'string',
        ]);

        if($validator->fails()){
            return response()->json([
                'status'    =>'failed validate',
                'error'     =>$validator->errors()
            ],400);
        }

        try {

            $user = User::findOrFail($id);

            //Get firebase auth data
            $auth = app('firebase.auth');
            $userFirebase = $auth->getUserByEmail($user->email);
            $userFirebaseUid = $userFirebase->uid;

            if ($request->input('name')) {
                $user->name = $request->input('name');
            }
            if ($request->input('email')) {
                $user->email    = $request->input('email');
            }
            if ($request->input('password')){
                $user->password = Hash::make($request->get('password'));
            }
            if ($request->input('birth_date')) {
                $user->birth_date = $request->input('birth_date');
            }
            if ($request->input('contact')){
                $user->contact = $request->input('contact');
            }
            if ($request->input('address')) {
                $user->address = $request->input('address');
            }
            if ($request->input('jenjang')) {
                $user->jenjang = $request->input('jenjang');
            }

            $user->save();

            //Update firebase auth data
            if ($request->input("email")) {
                $auth->changeUserEmail($userFirebaseUid, $request->input("email"));
            }

            if ($request->input("password")) {
                $auth->changeUserPassword($userFirebaseUid, $request->input("password"));
            }
            //End of Update firebase auth data

            return ResponseHelper::response(
                "Berhasil mengedit profil",
                $user,
                200,
                "Success"
            );

            return response()->json([
                'status'    => 'Success',
                'message'   => "Success update user",
                'user'      => $user
            ],200);

        } catch (\Throwable $th) {
            return ResponseHelper::response(
                "Gagal mengedit profil",
                null,
                400,
                "Failed"
            );
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

            if($user->role == Role::ROLE["TUTOR"]){
                $userId = $user->id;
                $tutor = User::where('id', $userId)
                        ->with(array(
                            'detail',
                            'tutorSubject'=>function($query){
                                $query->leftJoin('subject', 'subject.id', '=', 'tutor_subject.subject_id');
                            }, 'rating'=>function($query){
                                $query->selectRaw('target_id,AVG(rate) average')
                                ->groupBy('target_id');
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
                'status'    =>'failed',
                'error'     =>$validator->errors()
            ],400);
        }
        try{
            $user = User::where('email',$request->email)->first();
            $user->password = $pw;
            $user->save();

            $email = $request->email;

            // Change password firebase
            $auth = app('firebase.auth');
            $user = $auth->getUserByEmail($email);
            $auth->changeUserPassword($user->uid, $password);
            // End of change password firebase

        }catch(\throwable $e){
            return response()->json([
                'status'    => 'failed',
                'message'   => 'user not found'],404);
        }

        try{

            $data = Information::get();

            foreach ($data as $key) {
                if ($key->variable == "no_telp") {
                    $no_telp = $key->value;
                }

                if ($key->variable == "alamat") {
                    $alamat = $key->value;
                }

            }

            Mail::send([], [], function ($message) use ($request, $password, $alamat, $no_telp)
            {
                $message->subject('Password Baru Akun HaiTutor');
                $message->to($request->email);
                $view = View::make('otpVerification', [
                    Otp::OTP_PAYLOAD["OTP"] => $password,
                    Otp::OTP_PAYLOAD["TITLE"] => "Reset Password",
                    Otp::OTP_PAYLOAD["TYPE"] => Otp::OTP_TYPE["RESET_PASSWORD"],
                    Otp::OTP_PAYLOAD["NO_TELP"] => $no_telp,
                    Otp::OTP_PAYLOAD["ALAMAT"] => $alamat,
                    Otp::OTP_PAYLOAD["ACTION_USER"] => "Jika Anda tidak merasa melakukan permintaan ini, segera hubungi Admin HaiTutor melalui tombol berikut:",
                    Otp::OTP_PAYLOAD["MESSAGE"] => "Anda telah mengajukan reset password. Berikut password baru Anda, mohon untuk segera mengganti password setelah masuk ke akun Anda.",
                ]);

                $html = $view->render();
                $message->setBody($html,'text/html');
                // $message->setBody('<p> Hi!! </p><h1>password anda</h1><br/><h1><b>'.$password.'</b></h1>','text/html');

            });
        }catch(\throwable $e){
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed sending to email'
            ],403);
        }
        return response()->json([
            'status' => 'success',
            'message'=> 'password has been changed',
            'data' => array(
                'user'     => $user,
                'password' => $password
            )
        ],200);
    }

    public function logout(Request $request){

        $dataLog = [
            "USER" => JWTAuth::parseToken()->authenticate(),
            "USER_IP" => $request->ip()
        ];
        LogApps::logout($dataLog);

        $token = JWTAuth::getToken();
        JWTAuth::setToken($token)->invalidate();

        return response()->json([
            'status'    =>  'success',
            'message'   =>  'Token now is invalidated'
            ]
        );
    }

    public function getAllStudent(Request $request){
        $paginate = 10;
        if($request->get('paginate')) {
            $paginate = $request->get('paginate');
        }

        if($request->get('search')){
            $querySearch    = $request->get('search');
            $data           = User::where('role', Role::ROLE["STUDENT"])
                                ->where('is_deleted', User::DELETED_STATUS["ACTIVE"])
                                ->where(function ($where) use ($querySearch){
                                    $where->where('name','LIKE','%'.$querySearch.'%');
                                })->paginate($paginate);
            return $data;
        }

        $data   = User::where('role', Role::ROLE["STUDENT"])
                        ->where('is_deleted', User::DELETED_STATUS["ACTIVE"])
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

            if ($user->role == Role::ROLE["TUTOR"]) {
                $user->room_vc()->delete();
                $user->room_chat()->delete();
                $user->history_vc()->delete();
            }

            // CloudKilatHelper::delete($user->photo);
            GoogleCloudStorageHelper::delete($user->photo);
            $user->is_deleted   = User::DELETED_STATUS["DELETED"];
            $user->save();

            if($user){
                return response([
                    "status"	=> "Success",
                    "message"   => "Success delete user"
                ]);
            } else {
                return response([
                    "status"    => "Failed",
                    "message"   => "Failed to delete data"
                ]);
            }

        } catch (\Throwable $th) {
            return response([
                "status"	=> "failed",
                "message"   => "failed to delete user"
            ]);
        }
    }

    public function getInformation(Request $request)
    {
        $message = "Get Informations Succeeded";
        $status = "Success";
        try {
            $data = Information::get();

            $disbursementPriceToken = 0;
            $disbursementPricePercentage = 0;
            $pricePerToken = 0;

            foreach ($data as $key => $value) {
                if ($value->variable == "disbursement_price_token") {
                    $disbursementPriceToken = $value->value;
                }

                if ($value->variable == "disbursement_price_percentage") {
                    $disbursementPricePercentage = $value->value;
                }

                if ($value->variable == "price_per_token") {
                    $pricePerToken = $value->value;
                }
            }

            $disbursementPriceToken = strval($disbursementPricePercentage / 100 * $pricePerToken)   ;

            foreach ($data   as $key => $value) {
                if ($value->variable == "disbursement_price_token") {
                    $value->value = $disbursementPriceToken;
                }
            }

            return response()->json(compact('data','status','message'),200);
        } catch (\Throwable $th) {
            $status      = 'Failed';
            $message    = 'Get Informations Failed';
            return response()->json(compact('data','status','message'),500);
        }
    }

    public function checkUpdate($versionCode)
    {
        $message = "Check Version Succeeded";
        $status = "Success";
        try {
            $lastData = AppVersion::where('versionCode', AppVersion::max('versionCode'))->latest('id')->first();
            if($versionCode < $lastData->versionCode){
                $mustUpdate = AppVersion::select('type')->where('versionCode', '>', $versionCode)->distinct()->pluck('type')->toArray();
                if (count($mustUpdate) > 0 && in_array(1, $mustUpdate)){
                    $lastData->type = 1;
                } else {
                    $lastData->type = 0;
                }
                $data = $lastData;
            } else {
                $data = [];
            }

            return response()->json(compact('data','status','message'),200);
        } catch (\Exception $e) {
            $status    = 'Failed';
            $message   = 'Get Dashboard Statistics Failed';
            $error     = $e->getMessage();
            return response()->json(compact('error','status','message'),500);
        }
    }

    public function requestVerification()
    {
        try {
            $user           = JWTAuth::parseToken()->authenticate();
            $tutor          = TutorDetail::where('user_id', '=', $user->id)->firstOrFail();
            $tutor->status  = TutorDetail::TutorStatus["PENDING"];
            $tutor->save();

            $dataNotif = [
                "title"         => "HaiTutor",
                "message"       => $user->name . " mengajukan permohonan verifikasi akun tutor",
                "action"        => Notification::NOTIF_ACTION["TUTOR_VERIFICATION"],
                "channel_name"  => Notification::CHANNEL_NOTIF_NAMES[8]
            ];
            FCM::pushNotificationAdmin($dataNotif);

            return response()->json([
                'status'    =>  'Success',
                'message'   =>  'Request Verification Sent',
                'data'      =>  $tutor
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'message'   =>  'Request Verification Failed',
                'data'      =>  $th->getMessage()
            ]);
        }
    }

    public function suspendUser($id)
    {
        try {
            $user               = User::findOrFail($id);
            $user->isRestricted = User::IS_RESTRICTED["TRUE"];
            $user->save();

            return response()->json([
                'status'    =>  'Success',
                'message'   =>  'Suspend User Succeeded',
                'data'      =>  $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'    =>  'Failed',
                'message'   =>  'Suspend User Failed',
                'data'      =>  $e->getMessage()
            ]);
        }
    }

    public function unsuspendUser($id)
    {
        try {
            $user               = User::findOrFail($id);
            $user->isRestricted = User::IS_RESTRICTED["FALSE"];
            $user->save();

            return response()->json([
                'status'    =>  'Success',
                'message'   =>  'Unsuspend User Succeeded',
                'data'      =>  $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'    =>  'Failed',
                'message'   =>  'Unsuspend User Failed',
                'data'      =>  $e->getMessage()
            ]);
        }
    }

    public function checkUserIsRestricted()
    {
        try {

            $user           = JWTAuth::parseToken()->authenticate();

            if ($user->isRestricted == User::IS_RESTRICTED["TRUE"]) {
                return response()->json([
                    'status'    =>  'success',
                    'message'   =>  'User restricted',
                    'data'      =>  [
                        "is_restricted"  => User::IS_RESTRICTED["TRUE"]
                        ]
                    ], 200);
            } else {
                return response()->json([
                    'status'    =>  'success',
                    'message'   =>  'User not restricted',
                    'data'      =>  [
                            "is_restricted"  => User::IS_RESTRICTED["FALSE"]
                        ]
                    ], 200);
            }

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'message'   =>  'failed to fetch user restricted status',
                'data'      =>  $th->getMessage()
            ], 400);
        }
    }

    public function getStorageTokenCredentials()
    {
        try {
            $scopes = ["https://www.googleapis.com/auth/devstorage.read_only"];

            $googleClient = new Google_Client;
            $googleClient->setAuthConfig(base_path()."/haitutor-storage-read-only-user.json");
            $googleClient->setScopes($scopes);
            $googleClient->fetchAccessTokenWithAssertion();

            $token = $googleClient->getAccessToken();

            return response()->json([
                    'status'    =>  'success',
                    'message'   =>  'Fetch storage token credentials',
                    'data'      =>  array(
                        "token_credentials" => $token["access_token"],
                        "token_type"        => "Bearer"
                    )
                ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'message'   =>  'failed to fetch storage token credentials',
                'data'      =>  $th->getMessage()
            ], 400);
        }
    }

    public function getUserByRole(Request $request)
    {
        try {
            if($request->get('role') == Role::ROLE["ADMIN"]){
                // If Requested Role is Admin
                if($request->get('search')){
                    $query  = $request->get('search');
                    $data   = User::where(function ($where) use ($query){
                                $where->where('name','LIKE','%'.$query.'%')
                                ->orWhere('email','LIKE','%'.$query.'%')
                                ->orWhere('address','LIKE','%'.$query.'%');
                            })
                            ->where('role', Role::ROLE["ADMIN"])
                            ->with(array("admin_detail" => function($query){
                                $query->select("*");
                            }))->paginate(10);
                }else{
                    $data   = User::where('role',Role::ROLE["ADMIN"])
                                ->with(array("admin_detail" => function($query){
                                    $query->select("*");
                                }))->paginate(10);
                }

            } else {
                // If Requested Role is not Admin
                if($request->get('search')){
                    $query  = $request->get('search');
                    $data   = User::where('role', $request->get('role'))
                                ->where('name','LIKE','%'.$query.'%')
                                ->where('is_deleted', User::DELETED_STATUS["ACTIVE"])
                                ->paginate(10);
                } else {
                    $data = User::where('role', $request->get('role'))
                                ->where('is_deleted', User::DELETED_STATUS["ACTIVE"])
                                ->paginate(10);
                }
            }

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function getDetailUser($id){
        try {
            $data   = User::findOrFail($id);
            if($data->role == Role::ROLE["ADMIN"]){
                $data   = User::where("id", $id)
                            ->with(array("admin_detail" => function($query){
                                $query->select("*");
                            }))->first();
            }

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Get Detail User Succeeded'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'data'      =>  'No Data Picked',
                'message'   =>  'Get Detail User Failed'
            ]);
        }
    }

    public function updateUser(Request $request, $id){
        try {
            $user               = User::findOrFail($id);

            if($request->get('name')){
                $user->name    = $request->get('name');
            }

            if($request->get('email')){
                $user->email    = $request->get('email');
            }

            if($request->get('password')){
                $user->password    = $request->get('password');
            }

            if($request->get('contact')){
                $user->contact    = $request->get('contact');
            }

            if($request->get('address')){
                $user->address    = $request->get('address');
            }

            if($request->file('photo')){
                GoogleCloudStorageHelper::delete('/photos/user'.$user->photo);
                $user->photo    = GoogleCloudStorageHelper::put($request->file('photo'), '/photos/user', 'image', $user->id);
            }

            $user->save();

            return response()->json([
                'status'    => 'Success',
                'message'   => 'Update User Succeeded',
                'error'     => $user], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'Failed',
                'message'   => 'Update User Failed',
                'error'     => $e->getMessage()], 500);
        }
    }

    public function getSignedUrl(Request $request)
    {
        try {
            $file_path = $request->input("file_path");

            $signedUrl = GoogleCloudStorageHelper::getSignedUrl($file_path);

            return response()->json([
                    'status'    =>  'Success',
                    'message'   =>  'Fetch storage token credentials',
                    'data'      =>  array(
                        "signed_url" => $signedUrl
                    )
                ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'message'   =>  'failed to fetch storage token credentials',
                'data'      =>  $th->getMessage()
            ], 400);
        }
    }

    public function verifyUser($id)
    {
        try {
            $user           = User::findOrFail($id);
            $user->status   = User::STATUS["VERIFIED"];
            $user->save();

            return response()->json(
                [
                    'status'    =>  'Success',
                    'message'   =>  'User Verification Succeed',
                    'data'      =>  $user
                ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'message'   =>  'User Verification Failed',
                'data'      =>  $th->getMessage()
            ], 400);
        }
    }

    public function unverifyUser($id)
    {
        try {
            $user           = User::findOrFail($id);
            $user->status   = User::STATUS["UNVERIFIED"];
            $user->save();

            return response()->json(
                [
                    'status'    =>  'Success',
                    'message'   =>  'User Unverification Succeed',
                    'data'      =>  $user
                ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'message'   =>  'User Unverification Failed',
                'data'      =>  $th->getMessage()
            ], 400);
        }
    }
}
