<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\RoomVC;
use App\User;
use App\Libraries\Agora\RtcTokenBuilder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use JWTAuth;

class RoomVCController extends Controller
{
    public function index(Request $request)
    {
        try {
            if($request->get('search')){
                $query = $request->get('search');
                $data = RoomVC::where(function ($where) use ($query){
                    $where->where('status','LIKE','%'.$query.'%');
                } )->with(array('user'=>function($query){
                    $query->select('id','name','email');
                },'tutor'=>function($query){
                    $query->select('id','name','email');
                }))->paginate(10);    
            }else{
                $data = RoomVC::with(array('user'=>function($query){
                    $query->select('id','name','email');
                },'tutor'=>function($query){
                    $query->select('id','name','email');
                }))->paginate(10);
            }
                
            return response()->json($data);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'data'      =>  'No Data Picked',
                'message'   =>  'Get Data Failed'
            ]);
        }
    }
    public function createRoom($tutor_id)
    {   

        // if(class_exists(RtcTokenBuilder::class)){
        //     return response()->json([
        //         'status'    =>  'failed',
        //         'message'   =>  'ono class es'
        //     ]);
        // }

        //Agora config
        $appId = "702f2dc020744429a81b562e196e0922";
        $appCertificate = "2bdda327ef1e49a9acbc57158cfeb0a7";
        $channel_name = Str::random(16);
        $role = RtcTokenBuilder::RoleAttendee;

        $token = RtcTokenBuilder::buildTokenWithUid($appId, $appCertificate, $channel_name, 0, $role, 0);

        try {

            $user               =   JWTAuth::parseToken()->authenticate();
            $cekRoom            =   RoomVC::where("user_id",$user->id)
                                            ->where("tutor_id",$tutor_id)->first();
            $cekTutor           =   User::findOrFail($tutor_id);                                
            if($cekRoom){
                return response()->json([
                    'status'    =>  'success',
                    'message'   =>  'Room aleready created',
                    'data'      =>  $cekRoom
                ]);  
            }if(!$cekTutor){
                return response()->json([
                    'status'    =>  'failed',
                    'message'   =>  'Tutor not found'
                ]);
            }if($cekTutor->role!="tutor"){
                return response()->json([
                    'status'    =>  'failed',
                    'message'   =>  'Invalid tutor'
                ]);
            }
            $data               =   new RoomVC();
            $data->channel_name =   $channel_name;
            $data->token        =   $token;
            $data->tutor_id     =   $tutor_id;
            $data->user_id      =   $user->id;
            $data->save();
            return response()->json([
                'status'    =>  'success',
                'message'   =>  'Room Created',
                'data'      =>  $data
            ],200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'message'   =>  'Cannot Create Room',
                'data'      =>  array(
                    'token'         => $token,
                    'channel_name'  => $channel_name
                )
            ]);
        }
    }
    public function getMyRoom($roomkey)
    {
        $room   =   RoomVC::where('channel_name',$roomkey)
                            ->with(array('user'=>function($query){
                                $query->select('id','name','email');
                            },'tutor'=>function($query){
                                $query->select('id','name','email');
                            },'chat'=>function($query){
                                $query->where('deleted_at',null);
                            }))->first();
        return  $room;
    }

    public function showRoom()
    {
        try {
            $user   =   JWTAuth::parseToken()->authenticate();
            $data   =   RoomVC::where('user_id',$user->id)
                                ->orWhere('tutor_id',$user->id)
                                ->with(array('user'=>function($query){
                                    $query->select('id','name','email');
                                },'tutor'=>function($query){
                                    $query->select('id','name','email','photo')
                                    ->with(array('tutorSubject'=>function($query){
                                        $query->leftJoin('subject', 'subject.id', '=', 'tutor_subject.subject_id');
                                    }));
                                }))->get();
            return $data;                                   
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}