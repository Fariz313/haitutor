<?php

namespace App\Http\Controllers;

use App\User;
use App\AdminDetail;
use App\ApiAllowed;
use App\Chat;
use App\Helpers\GoogleCloudStorageHelper;
use App\Helpers\ResponseHelper;
use App\Notification;
use App\RoomChat;
use App\RoomVC;
use App\Order;
use App\Report;
use App\Role;
use App\TutorDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Illuminate\Support\Str;
use DB;
use FCM;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            if($request->get('search')){
                $query = $request->get('search');
                $data = User::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%')
                        ->orWhere('email','LIKE','%'.$query.'%')
                        ->orWhere('address','LIKE','%'.$query.'%');
                        } )->where('role', Role::ROLE["ADMIN"])->with(array("admin_detail" => function($query)
                        {
                            $query->select("*");
                        }))->paginate(10);
            }else{
                $data = User::where('role',Role::ROLE["ADMIN"])->with(array("admin_detail" => function($query)
                {
                    $query->select("*");
                }))->paginate(10);
            }

            return response()->json([
                'status'    =>  'success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'data'      =>  'No Data Picked',
                'message'   =>  'Get Data Failed'
            ]);
        }
    }

    public function dashboard()
    {
        try {
            $active_room_chat = RoomChat::where('status', 'open')->get();

            $user_active    = RoomChat::select('user_id')->where('status', 'open');
            $tutor_active   = RoomChat::select('tutor_id')->where('status', 'open');
            $active_user_in_room_chat = $user_active->union($tutor_active)->get();

            $active_room_vidcall = RoomVC::where('status', 'open')->get();

            $user_active    = RoomVC::select('user_id')->where('status', 'open');
            $tutor_active   = RoomVC::select('tutor_id')->where('status', 'open');
            $active_user_in_room_vidcall = $user_active->union($tutor_active)->get();

            $tempDate = \Carbon\Carbon::today();
            $transaction_today = Order::where('type_code', 1)
                                ->where('status', 'completed')
                                ->where('created_at', '>=', $tempDate)
                                ->get();

            $active_user_in_transaction_today = Order::select('user_id')
                                ->where('type_code', 1)
                                ->where('status', 'completed')
                                ->where('created_at', '>=', $tempDate)->distinct()->get();

            $report_today = Report::where('created_at', '>=', $tempDate)
                                ->get();

            $active_report_today = Report::where('created_at', '>=', $tempDate)
                                ->distinct()->get();

            return response()->json([
                'status'    => 'Success',
                'message'   => 'Get Dashboard Statistics Succeeded',
                'data'      => [
                    'count_active_room_chat'                => count($active_room_chat),
                    'count_active_user_in_room_chat'        => count($active_user_in_room_chat),
                    'count_active_room_vidcall'             => count($active_room_vidcall),
                    'count_active_user_in_room_vidcall'     => count($active_user_in_room_vidcall),
                    'count_transaction_today'               => count($active_user_in_transaction_today),
                    'count_active_user_in_transaction_today'=> count($transaction_today),
                    'count_report_today'                    => count($report_today),
                    'count_active_user_in_report_today'     => count($active_report_today)
                ]], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'Failed',
                'message'   => 'Get Dashboard Statistics Failed',
                'error'     => $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        try {
            if($request->get('role') == Role::ROLE["ADMIN"]){

                $registrator      =   JWTAuth::parseToken()->authenticate();

                $validator = Validator::make($request->all(), [
                    'name'          => 'required|string|max:255',
                    'photo'         => 'file',
                    'contact'       => 'required|string|max:20',
                    'address'       => 'required|string',
                    'nip'           => 'required|string|unique:admin_detail',
                ]);

                if($validator->fails()){
                    return response()->json([
                        'status'    => 'Failed',
                        'error'     => $validator->errors()
                    ],400);
                }

                $role               = Role::ROLE["ADMIN"];

                if($request->get('email')){
                    $email    = $request->get('email');
                } else {
                    $email    = strtolower($role->name) . "@haitutor.id";
                }

                $user = User::create([
                    'name'          => $request->get('name'),
                    'email'         => $email,
                    'password'      => Hash::make('haitutor123'),
                    'birth_date'    => '01/01/2001',
                    'role'          => Role::ROLE["ADMIN"],
                    'contact'       => $request->get('contact'),
                    'address'       => $request->get('address')
                ]);

                if($request->file('photo')){
                    $user->photo    = GoogleCloudStorageHelper::put($request->file('photo'), '/photos/user', 'image', $user->id);
                }

                $user->status       = User::STATUS["VERIFIED"];
                $user->save();

                $user_detail                    = new AdminDetail();
                $user_detail->nip               = $request->get('nip');
                $user_detail->user_id           = $user->id;
                $user_detail->registrator_id    = $registrator->id;
                $user_detail->save();

                //Add user to Firebase Authentication
                $userProperties = [
                    "email" => $email,
                    "password"  => "haitutor123"
                ];

                $auth = app('firebase.auth');
                $auth->createUser($userProperties);
                //End Add user to Firebase Authentication

                return response()->json([
                    'status'    => 'Success',
                    'message'   => 'Register User Succeeded',
                    'error'     => $user], 200);

            } else {

                $validator = Validator::make($request->all(), [
                    'name'          => 'required|string|max:255',
                    'email'         => 'required|string|email|max:255|unique:users',
                    'password'      => 'required|string|min:6',
                    'birth_date'    => 'required|date',
                    'contact'       => 'required|string|max:20',
                    'address'       => 'required|string'
                ]);

                if($validator->fails()){
                    return response()->json([
                        'status'    =>  'failed',
                        'error'     =>  $validator->errors()
                    ],400);
                }

                try {
                    $user = User::create([
                        'name'          => $request->get('name'),
                        'email'         => $request->get('email'),
                        'password'      => Hash::make($request->get('password')),
                        'birth_date'    => $request->get('birth_date'),
                        'role'          => $request->get('role'),
                        'contact'       => $request->get('contact'),
                        'company_id'    => $request->get('company_id'),
                        'address'       => $request->get('address'),
                    ]);

                    if ($user->role == Role::ROLE["TUTOR"]) {
                        $detail = new TutorDetail();
                        $detail->user_id    = $user->id;
                        $detail->status     = 'unverified';
                        $detail->biography  = '-';

                        $detail->save();
                    }

                    //Add user to Firebase Authentication
                    $userProperties = [
                        "email" => $request->get("email"),
                        "password"  => $request->get("password")
                    ];

                    $auth = app('firebase.auth');
                    $auth->createUser($userProperties);
                    //End Add user to Firebase Authentication

                    return ResponseHelper::response(
                        "Berhasil menambah user",
                        null,
                        200,
                        "Success"
                    );

                } catch (\Throwable $th) {
                    return ResponseHelper::response(
                        "Gagal menambah user",
                        null,
                        400,
                        "Failed"
                    );
                }
            }
        } catch (\Exception $e) {
            return ResponseHelper::response(
                "Gagal menambah user",
                null,
                400,
                "Failed"
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        try {
            $credentials = $request->only('email', 'password');
            JWTAuth::factory()->setTTL(14400);
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'status'    => 'Failed',
                    'error'     => 'invalid_credentials',
                    'message'   => 'Email or Password is Wrong'], 400);
            }

            $user = JWTAuth::user();
            $allowedRole    = array(
                Role::ROLE["ADMIN"],
                Role::ROLE["PUBLISHER"]
            );

            if(in_array($user->role, $allowedRole)){
                return response()->json([
                    'status'    => 'Success',
                    'token'     => $token,
                    'message'   => 'Logged in successfully',
                    'logged'    => 'true',
                    'role'      => (int)$user->role
                ], 200);
            } else {
                return response()->json([
                    'status'    => 'Failed',
                    'message'   => 'Forbidden Access'
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'Failed',
                'error'     => $e->getMessage(),
                'message'   => 'Cant create authentication, please try again'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showAdmin($id)
    {
        try {

            $admin = User::where("id", $id)->with(array("admin_detail" => function($query)
            {
                $query->select("*");
            }))->first();

            return response([
                "status"	=> "failed",
                "message"   => "Success fetch admin",
                "data"      => $admin
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $notFound) {
            DB::rollback();
            return response([
                "status"	=> "failed",
                "message"   => "Admin not found"
            ], 400);
        } catch (\Throwable $th) {
            DB::rollback();
            return response([
                "status"	=> "failed",
                "message"   => "failed to get admin by id",
                "data"      => $th->getMessage()
            ], 400);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateAdmin(Request $request, $id)
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
        try {
            $user = User::where("id", $id)->with(array("admin_detail" => function($query)
            {
                $query->select("*");
            }))->first();
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

            $admin_detail = AdminDetail::where("user_id", $id)->firstOrFail();

            if ($request->input('nip')) {
                $admin_detail->nip = $request->input('nip');
            }

            DB::beginTransaction();

            $admin_detail->save();
            $user->save();

            DB::commit();

            $user = User::where("id", $id)->with(array("admin_detail" => function($query)
            {
                $query->select("*");
            }))->first();

            return response()->json([
                'status'    => 'success',
                'message'   => "Success update admin",
                'user'      => $user
            ],200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $notFound) {
            DB::rollback();
            return response()->json([
                'status'    => 'failed',
                'message'   => "Admin not found"
            ],400);
        }catch (\Throwable $th) {
            DB::rollback();
            return response()->json([
                'status'    => 'failed',
                'message'   => "Failed to update admin",
                'user'      => $user,
                'data'      => $th->getMessage()
            ],400);
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroyAdmin($id)
    {

        try {

            $current_user = JWTAuth::parseToken()->authenticate();

            if ($current_user->id == $id) {

                return response([
                    "status"	=> "failed",
                    "message"   => "You can't delete yourself"
                ], 400);

            } else {

                $admin = User::where("id", $id)->firstOrFail();

                if ($admin->role == ROLE::ROLE["ADMIN"]) {
                    $delete_detail = AdminDetail::where("user_id", $id)->firstOrFail();

                    DB::beginTransaction();

                    $delete_detail->delete();
                    $admin->delete();

                    DB::commit();
                    return response([
                        "status"	=> "success",
                        "message"   => "Success delete admin"
                    ], 200);

                } else {
                    return response([
                        "status"	=> "failed",
                        "message"   => "You're not deleting admin"
                    ], 400);
                }
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $notFound) {
            DB::rollback();
            return response([
                "status"	=> "failed",
                "message"   => "Admin not found"
            ], 400);
        } catch (\Throwable $th) {
            DB::rollback();
            return response([
                "status"	=> "failed",
                "message"   => "failed to delete admin",
                "data"      => $th->getMessage()
            ], 400);
        }
    }

    public function getApiAllowed(Request $request){
        try {
            $data = ApiAllowed::where('action_url', $request->get('action_url'))->where('action_method', $request->get('action_method'))->first();
            return $data;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function chatAdminToUser(Request $request, $userId){
        return $this->sendChat($request, $userId);
    }

    public function broadcastChatAdmin(Request $request){
        try {
            $successUser    = array();
            $failedUser     = array();

            foreach(json_decode($request->input('users')) as $userId){
                $result = $this->sendChat($request, $userId)->getData();
                if($result->status == User::RESPONSE_STATUS["SUCCESS"]){
                    array_push($successUser, $userId);
                } else {
                    array_push($failedUser, $userId);
                }
            }

            return response()->json([
                'status'    => 'Success',
                'message'   => 'Broadcast Chat Succeeded',
                'data'      => array(
                    "successUser"   => $successUser,
                    "failedUser"    => $failedUser,
                )
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'Failed',
                'message'   => 'Broadcast Chat Failed',
                'error'     => $e->getMessage()], 500);
        }
    }

    private function sendChat(Request $request, $userId){
        try{
            $database = app('firebase.database');

            $currentAdmin   = JWTAuth::parseToken()->authenticate();
            $user           = User::findOrFail($userId);

            if($user->role == Role::ROLE["STUDENT"]){
                // If User is a student
                $checkRoom      = RoomChat::where("user_id", $userId)->where("tutor_id", $currentAdmin->id)->first();
            } else {
                // If User is a tutor
                $checkRoom      = RoomChat::where("user_id", $currentAdmin->id)->where("tutor_id", $userId)->first();
            }

            if (!$checkRoom) {
                // If Room Not Exists Yet
                try {
                    $dataRoom               = new RoomChat();
                    $dataRoom->room_key     = Str::random(6);
                    if($user->role == Role::ROLE["STUDENT"]){
                        $dataRoom->tutor_id = $currentAdmin->id;
                        $dataRoom->user_id  = $userId;
                    } else {
                        $dataRoom->tutor_id = $userId;
                        $dataRoom->user_id  = $currentAdmin->id;
                    }
                    $dataRoom->status           = RoomChat::ROOM_STATUS["OPEN"];
                    $dataRoom->last_message_at  = date("Y-m-d H:i:s");
                    $dataRoom->save();

                    $checkRoom = $dataRoom;

                    $roomData = [
                        'lastMessageAt' => date("d/m/Y H:i:s"),
                        'chat'          => [],
                        'id'            => $checkRoom->id,
                        'room_key'      => $checkRoom->room_key,
                        'status'        => RoomChat::ROOM_STATUS["OPEN"],
                        'tutor_id'      => $currentAdmin->id,
                        'user_id'       => (int)$userId
                    ];
                    $database->getReference('room_chat/'. $checkRoom->room_key)->set($roomData);

                } catch (\Throwable $th) {
                    return response()->json([
                        'status'            =>  'Failed',
                        'message'           =>  'Room Cant be Created',
                        'data'              =>  $th->getMessage()
                    ]);
                }
            }

            // return 'Success';

            try {
                DB::beginTransaction();

                // SEND CHAT
                $data                   = new Chat();
                $message                = "";
                $file                   = "";

                if ($request->input('text')) {
                    $data->text         = $request->input('text');
                    $message            = $request->input('text');
                }

                $data->user_id          = $user->id;
                $data->room_key         = $checkRoom->room_key;
                if($request->hasFile('file')){
                    try {
                        $message        = "Photo";
                        $file           = GoogleCloudStorageHelper::put($request->file('file'), "/photos/chat/", 'image', $user->id);

                        $data->file     = $file;
                        $data->save();

                    } catch (\Throwable $th) {
                        return response()->json([
                            'status'	=> 'failed',
                            'message'	=> 'failed adding ask with image',
                            "data"      => $th->getMessage()
                        ], 501);
                    }
                }

                $checkRoom->status          = RoomChat::ROOM_STATUS["OPEN"];
                $checkRoom->last_message    = $message;
                $checkRoom->save();

                $chatData = [
                    'created_at' => date("d/m/Y H:i:s"),
                    'file' => $file,
                    'id' => 0,
                    'message_readed' => false,
                    'readed_at' => '',
                    'room_key' => $checkRoom->room_key,
                    'text' => $data->text,
                    'user_id' => (int)$currentAdmin->id
                ];
                $newChatKey = $database->getReference('room_chat/'. $checkRoom->room_key .'/chat')->push()->getKey();
                $database->getReference('room_chat/'. $checkRoom->room_key .'/chat/' . $newChatKey)->set($chatData);

                if($data->save()){
                    $room = RoomChat::findOrFail($checkRoom->id);

                    // If Target is a tutor
                    $target = $room->tutor;
                    $sender = $room->user;

                    if($user->id == $room->user_id){
                        // If Target is a student
                        $target = $room->user;
                        $sender = $room->tutor;
                    }

                    $room->last_message_at = $data->created_at;
                    $room->last_message = $message;
                    $room->last_sender = $sender->id;
                    $room->last_message_readed = "false";
                    $room->last_message_readed_at = null;
                    $room->save();

                    $dataNotif = [
                        "title" => $sender->name,
                        "message" => $message,
                        "sender_id" => $sender->id,
                        "target_id" => $target->id,
                        'token_recipient' => $target->firebase_token,
                        "channel_name" => Notification::CHANNEL_NOTIF_NAMES[0],
                        "room_chat"    => $room,
                        'save_data' => false
                    ];
                    $responseNotif = FCM::pushNotification($dataNotif);

                    return response()->json([
                        'status'	=> 'Success',
                        'message'	=> 'Success adding chat',
                        'data'      => array(
                            "notif"     => json_decode($responseNotif),
                            "url_image" => $data->file
                        )
                    ], 201);
                }

                DB::commit();
                return response()->json([
                    'status'        =>  'Success',
                    'message'       =>  'Chat is sent',
                    'room_key'      =>  $checkRoom->room_key,
                    'data'          =>  $checkRoom
                ]);

            } catch (\Throwable $th) {
                DB::rollback();
                return response()->json([
                    'status'        =>  'failed',
                    'message'       =>  'Chat is not sent',
                    'data'          =>  $th->getMessage()
                ]);
            }

            return 'Success';

        } catch(\Throwable $e){
            return response()->json([
                'status'    =>  'failed2',
                'message'   =>  'Unable to create token transaction',
                'data'      =>  $e->getMessage()
            ]);
        }
    }
}
