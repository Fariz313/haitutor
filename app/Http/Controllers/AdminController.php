<?php

namespace App\Http\Controllers;

use App\User;
use App\AdminDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Mail;
use Illuminate\Support\Str;
use App\TutorDetail;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            if($request->get('search')){
                $query = $request->get('search');
                $data = User::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%')
                        ->orWhere('email','LIKE','%'.$query.'%')
                        ->orWhere('address','LIKE','%'.$query.'%');
                        } )->where('role','admin')->paginate(10);    
            }else{
                $data = User::where('role','admin')->paginate(10);
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
            if (! $token = JWTAuth::attempt($credentials,)) {
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
    public function show($id)
    {
        //
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
