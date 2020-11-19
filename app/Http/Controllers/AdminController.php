<?php

namespace App\Http\Controllers;

use App\User;
use App\AdminDetail;
use App\RoomChat;
use App\RoomVC;
use App\Order;
use App\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Mail;
use Illuminate\Support\Str;
use App\TutorDetail;
use DB;

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
                        } )->where('role','admin')->with(array("admin_detail" => function($query)
                        {
                            $query->select("*");
                        }))->paginate(10);
            }else{
                $data = User::where('role','admin')->with(array("admin_detail" => function($query)
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
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'email'         => 'required|string|email|max:255|unique:users',
            'password'      => 'required|string|min:6',
            'birth_date'    => 'required|date',
            'photo'         => 'file',
            'contact'       => 'required|string|max:20',
            'company_id'    => 'integer|max:20',
            'address'       => 'required|string',
            'nip'           => 'required|string|unique:admin_detail',
        ]);

        if($validator->fails()){
            return response()->json([
                'status'    =>'failed',
                'error'     =>$validator->errors()
            ],400);
        }
        $message = "Upload";
        try {
            $registrator      =   JWTAuth::parseToken()->authenticate();
            $user = User::create([
                'name'          => $request->get('name'),
                'email'         => $request->get('email'),
                'password'      => Hash::make($request->get('password')),
                'birth_date'    => $request->get('birth_date'),
                'role'          => "admin",
                'contact'       => $request->get('contact'),
                'company_id'    => $request->get('company_id'),
                'address'       => $request->get('address'),
            ]);
            $user_detail                = new AdminDetail();
            $user_detail->nip           = $request->get('nip');
            $user_detail->user_id       = $user->id;
            $user_detail->registrator_id= $registrator->id;
            $user_detail->save();
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
            $user       = 'no admin';
            $token      = 'no token';
            $message    = 'Failed To Create Admin';
            return response()->json(compact('user','token','message'),500);
        }



        return response()->json(compact('user','user_detail','token','message'),201);
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
                    'status'    => 'failed',
                    'error'     => 'invalid_credentials',
                    'message'   => 'Email or Password is Wrong'], 400);
            }
            $user = JWTAuth::user();
            if($user->role == "admin"){
                return response()->json([
                    'status'    => 'success',
                    'token'     => $token,
                    'message'   => 'Loggin is successfuly' ], 200);
                }
                return response()->json([
                    'status'    => 'failed',
                    'message'   => 'Not Admin' ], 400);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    => 'failed',
                'error'     => 'could_not_create_token',
                'message'   => 'Cant create authentication, please try again'], 500);
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

                if ($admin->role == "admin") {
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
}
