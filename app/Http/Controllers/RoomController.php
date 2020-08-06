<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\RoomChat;
use App\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use JWTAuth;

class RoomController extends Controller
{
    public function CreateRoom($tutor_id)
    {
        try {
            $user               =   JWTAuth::parseToken()->authenticate();
            $cekRoom            =   RoomChat::where("user_id",$user->id)
                                            ->where("tutor_id",$tutor_id)->first();
            $cekTutor           =   User::findOrFail($tutor_id);                                
            if($cekRoom){
                return response()->json([
                    'status'    =>  'failed',
                    'message'   =>  'Room aleready created'
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
            $data               =   new RoomChat();
            $data->room_key     =   Str::random(6); 
            $data->tutor_id     =   $tutor_id;
            $data->user_id      =   $user->id;
            $data->save();
            return response()->json([
                'status'    =>  'success',
                'message'   =>  'Room Created'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'message'   =>  'Cannot Create Room'
            ]);
        }
    }
    public function getMyRoom($roomkey)
    {
        $room   =   RoomChat::where('room_key',$roomkey)
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
            $data   =   RoomChat::where('user_id',$user->id)
                                ->orWhere('tutor_id',$user->id)
                                ->with(array('user'=>function($query){
                                    $query->select('id','name','email');
                                },'tutor'=>function($query){
                                    $query->select('id','name','email');
                                }))->get();
            return $data;                                   
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
