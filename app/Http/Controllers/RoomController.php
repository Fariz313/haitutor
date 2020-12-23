<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\RoomChat;
use App\User;
use App\Notification;
use App\Role;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use JWTAuth;
use FCM;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        try {
            if($request->get('search')){
                $query = $request->get('search');
                $data = RoomChat::where(function ($where) use ($query){
                    $where->where('room_key','LIKE','%'.$query.'%')
                        ->orWhere('chat_type','LIKE','%'.$query.'%');
                } )->with(array('user'=>function($query){
                    $query->select('id','name','email');
                },'tutor'=>function($query){
                    $query->select('id','name','email');
                },'chat'=>function($query){
                    $query->where('deleted_at',null);
                }))->paginate(10);
            }else{
                $data = RoomChat::with(array('user'=>function($query){
                    $query->select('id','name','email');
                },'tutor'=>function($query){
                    $query->select('id','name','email');
                },'chat'=>function($query){
                    $query->where('deleted_at',null);
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
    public function CreateRoom($tutor_id)
    {
        try {
            $user               =   JWTAuth::parseToken()->authenticate();
            $cekRoom            =   RoomChat::where("user_id",$user->id)
                                            ->where("tutor_id",$tutor_id)
                                            ->where("status", "open")->first();
            $cekTutor           =   User::findOrFail($tutor_id);
            if($cekRoom){
                return response()->json([
                    'status'    =>  'failed',
                    'message'   =>  'Room aleready created',
                    'room_key'  =>  $cekRoom->room_key,
                    'data'      =>  $cekRoom
                ]);
            }if(!$cekTutor){
                return response()->json([
                    'status'    =>  'failed',
                    'message'   =>  'Tutor not found'
                ]);
            }if($cekTutor->role != Role::ROLE["TUTOR"]){
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
                'message'   =>  'Room Created',
                'room_key'  =>  $data->room_key,
                'data'      =>  $data
            ],200);
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
    public function showRoom(Request $request)
    {
        try {
            $user   =   JWTAuth::parseToken()->authenticate();
            if($request->get('search')){
                $query = $request->get('search');

                if(Role::ROLE["STUDENT"] == $user->role){
                    $data   =   RoomChat::select('room_chat.*','tutor_table.name as tutor_name')
                                ->where(function($query) use ($user) {
                                    $query->where('user_id',$user->id)
                                        ->orWhere('tutor_id',$user->id);
                                })
                                ->where('tutor_table.name','LIKE','%'.$query.'%')
                                ->join('users as tutor_table', 'tutor_table.id', '=', 'room_chat.tutor_id')
                                ->with(array('user'=>function($query){
                                    $query->select('id','name','email','photo', 'status');
                                },'tutor'=>function($query){
                                    $query->select('id','name','email','photo', 'status')
                                    ->with(array('detail', 'tutorSubject'=>function($query){
                                        $query->leftJoin('subject', 'subject.id', '=', 'tutor_subject.subject_id');
                                    }));
                                }))
                                ->orderBy('room_chat.last_message_at', 'DESC')
                                ->get();
                } else {
                    $data   =   RoomChat::select('room_chat.*','user_table.name as user_name')
                                ->where(function($query) use ($user) {
                                    $query->where('user_id',$user->id)
                                        ->orWhere('tutor_id',$user->id);
                                })
                                ->where('user_table.name','LIKE','%'.$query.'%')
                                ->join('users as user_table', 'user_table.id', '=', 'room_chat.user_id')
                                ->with(array('user'=>function($query){
                                    $query->select('id','name','email','photo', 'status');
                                },'tutor'=>function($query){
                                    $query->select('id','name','email','photo', 'status')
                                    ->with(array('detail', 'tutorSubject'=>function($query){
                                        $query->leftJoin('subject', 'subject.id', '=', 'tutor_subject.subject_id');
                                    }));
                                }))
                                ->orderBy('room_chat.last_message_at', 'DESC')
                                ->get();
                }
                return $data;
            } else {
                $data   =   RoomChat::where('user_id',$user->id)
                                ->orWhere('tutor_id',$user->id)
                                ->with(array('user'=>function($query){
                                    $query->select('id','name','email','photo', 'status');
                                },'tutor'=>function($query){
                                    $query->select('id','name','email','photo', 'status')
                                    ->with(array('detail', 'tutorSubject'=>function($query){
                                        $query->leftJoin('subject', 'subject.id', '=', 'tutor_subject.subject_id');
                                    }));
                                }))
                                ->orderBy('room_chat.last_message_at', 'DESC')
                                ->get();
                return $data;
            }
        } catch (\Throwable $th) {
            return $th;
        }
        // return 'A';
    }

    public function showById($id)
    {
        try {

            $data   = RoomChat::where("id", $id)->with(array('user'=>function($query){
                $query->select('id','name','email','photo');
            },'tutor'=>function($query){
                $query->select('id','name','email','photo')
                ->with(array('tutorSubject'=>function($query){
                    $query->leftJoin('subject', 'subject.id', '=', 'tutor_subject.subject_id');
                }));
            }))->firstOrFail();

            return response()->json([
                "status"    => "success",
                "message"   => "Success fetch room chat",
                "data"      => $data
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                "status"    => "failed",
                "message"   => "Failed fetch room chat",
            ], 400);
        }
    }

    public function checkRoom(Request $request)
    {
        try {

            $user                   = JWTAuth::parseToken()->authenticate();

            if($request->get("tutorid")){
                $query              = $request->get("tutorid");
                $data               = RoomChat::where("user_id", $user->id)
                                    ->where("tutor_id", $query)
                                    ->where("status", "open")->first();


                if ($data){
                    return response()->json([
                        'status'    => 'success',
                        'message'   => 'Room exist',
                        'room_key'  => $data->room_key,
                        'data'      => $data
                    ]);
                } else {
                    return response()->json([
                        'status'    => 'failed',
                        'message'   => 'Room not exist'
                    ]);
                }
            }else {
                return response()->json([
                    'status'    => 'failed',
                    'message'   => 'Missing param'
                ]);
            }

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'message'   =>  'Failed to check room',
                'data'      =>  $th
            ]);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $message = "Update Status Room";
        $status = "Success";
        try {
            $room = RoomChat::findOrFail($id);
            $room->status = $request->input('status');
            $senderId = $request->input('sender_id');
            $message = "Update Status Room Succeed";
            $room->save();

            $sender = $room->user;
            $target = $room->tutor;
            if($senderId == $room->tutor->id){
                $sender = $room->tutor;
                $target = $room->user;
            }

            $messageNotif = "Sesi percakapan dengan " . $sender->name . " telah berakhir";
            $channelName  = Notification::CHANNEL_NOTIF_NAMES[11];
            if("open" == $room->status){
                $messageNotif = "Sesi percakapan dengan " . $sender->name . " dimulai";
                $channelName  = Notification::CHANNEL_NOTIF_NAMES[12];
            }

            $dataNotif = [
                "title" => "HaiTutor",
                "message" => $messageNotif,
                "sender_id" => $sender->id,
                "target_id" => $target->id,
                "channel_name"   => $channelName,
                'token_recipient' => $target->firebase_token,
                "room_chat"       => $room,
                'save_data' => true
            ];
            $responseNotif = FCM::pushNotification($dataNotif);

        } catch (\Throwable $th) {
            $status      = 'Failed';
            $message    = 'Update Room is Failed';
        }

        return response()->json([
            'status'    =>  $status,
            'message'   =>  $message,
            'data'      =>  $room
        ], 201);
    }

    public function destroy($id)
    {
        try {
            $room = RoomChat::where("id", $id)->first();

            $room->chat()->delete();
            $delete = $room->delete();

            if ($delete) {
                return response([
                    "status"	=> "success",
                    "message"   => "Success delete room chat"
                ], 200);
            } else {
                return response([
                    "status"    => "failed",
                    "message"   => "Failed to delete data"
                ], 400);
            }
        } catch (\Throwable $th) {
            return response([
                "status"	=> "failed",
                "message"   => "failed to delete room chat",
                "data"      => $th->getMessage()
            ], 400);
        }
    }

    public function updateStatusByAdmin($id)
    {
        try {
            $room = RoomChat::where("id", $id)->first();
            if ($room->status == "open") {
                $room->status = "closed";
            } else {
                $room->status = "open";
            }

            $room->save();

            return response([
                "status"	=> "success",
                "message"   => "Success update room chat status"
            ], 200);
        } catch (\Throwable $th) {
            return response([
                "status"	=> "failed",
                "message"   => "failed to update room chat status",
                "data"      => $th->getMessage()
            ], 400);
        }
    }
}
