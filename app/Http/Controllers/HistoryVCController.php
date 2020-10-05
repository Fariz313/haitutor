<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\HistoryVC;
use App\User;
use App\Libraries\Agora\RtcTokenBuilder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use JWTAuth;

class HistoryVCController extends Controller
{
    public function index(Request $request)
    {
        try {
            if($request->get('search')){
                $query = $request->get('search');
                $data = HistoryVC::where(function ($where) use ($query){
                    $where->where('status','LIKE','%'.$query.'%');
                } )->with(array('user'=>function($query){
                    $query->select('id','name','email');
                },'tutor'=>function($query){
                    $query->select('id','name','email');
                }))->paginate(10);
            }else{
                $data = HistoryVC::with(array('user'=>function($query){
                    $query->select('id','name','email');
                },'tutor'=>function($query){
                    $query->select('id','name','email');
                }))->paginate(10);
            }
            return response()->json($data);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'data'      =>  $th->getMessage(),
                'message'   =>  'Get Data Failed'
            ]);
        }
    }
    public function createHistory(Request $request, $tutor_id)
    {
        try {

            $validator = Validator::make($request->all(), [
                'room_id' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'    =>'failed',
                    'message'   => 'Missing parameter',
                    'error'     =>$validator->errors()
                ], 400);
            }
            $current_user           = JWTAuth::parseToken()->authenticate();

            $history                = new HistoryVC();
            $history->room_id       = $request->input("room_id");
            $history->user_id       = $current_user->id;
            $history->tutor_id      = $tutor_id;
            $history->duration      = $request->input("duration");
            $history->save();

            return response()->json([
                'status'    =>  'success',
                'message'   =>  'Video Call history created',
                'room_key'  =>  $history->id
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'message'   =>  'Failed to insert history',
                'data'      =>  $th->getMessage()
            ], 500);
        }
    }

    public function updateHistory(Request $request, $id)
    {
        try {

            $validator = Validator::make($request->all(), [
                'duration' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'    => 'failed',
                    'message'   => 'Missing parameter',
                    'error'     => $validator->errors()
                ], 400);
            }
            $history            = HistoryVC::findOrFail($id);

            $history->duration  = $request->input("duration");
            $history->save();

            return response()->json([
                'status'    =>  'success',
                'message'   =>  'Video Call history updated'
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'message'   =>  'Failed to insert history',
                'data'      =>  $th->getMessage()
            ], 500);
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

    public function showRoom(Request $request)
    {
        try {
            $user   =   JWTAuth::parseToken()->authenticate();

            if ($request->get("search")) {

                $query = $request->get("search");

                if ($user->role == "student") {
                    $data       =   HistoryVC::select('history_vc.*','tutor_table.name as tutor_name')
                                        ->where(function($query) use ($user) {
                                            $query->where('user_id',$user->id)
                                                ->orWhere('tutor_id',$user->id);
                                        })
                                        ->where('tutor_table.name','LIKE','%'.$query.'%')
                                        ->join('users as tutor_table', 'tutor_table.id', '=', 'history_vc.tutor_id')
                                        ->with(array('user'=>function($query){
                                            $query->select('id','name','email');
                                        },'tutor'=>function($query){
                                            $query->select('id','name','email','photo')
                                            ->with(array('tutorSubject'=>function($query){
                                                $query->leftJoin('subject', 'subject.id', '=', 'tutor_subject.subject_id');
                                            }));
                                        }))->paginate(10);

                    return response()->json($data, 200);
                } else if ($user->role == "tutor") {
                    $data       =   HistoryVC::select('history_vc.*','user_table.name as user_name')
                                        ->where(function($query) use ($user) {
                                            $query->where('user_id',$user->id)
                                                ->orWhere('tutor_id',$user->id);
                                        })
                                        ->where('user_table.name','LIKE','%'.$query.'%')
                                        ->join('users as user_table', 'user_table.id', '=', 'history_vc.user_id')
                                        ->with(array('user'=>function($query){
                                            $query->select('id','name','email', 'photo');
                                        },'tutor'=>function($query){
                                            $query->select('id','name','email','photo')
                                            ->with(array('tutorSubject'=>function($query){
                                                $query->leftJoin('subject', 'subject.id', '=', 'tutor_subject.subject_id');
                                            }));
                                        }))->paginate(10);

                    return response()->json($data, 200);
                }

            } else {

                $data   =   HistoryVC::where('user_id',$user->id)
                                ->orWhere('tutor_id',$user->id)
                                ->with(array('user'=>function($query){
                                    $query->select('id','name','email');
                                },'tutor'=>function($query){
                                    $query->select('id','name','email','photo')
                                    ->with(array('tutorSubject'=>function($query){
                                        $query->leftJoin('subject', 'subject.id', '=', 'tutor_subject.subject_id');
                                    }));
                                }))->paginate(10);
                return response()->json($data, 200);

            }

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'message'   =>  'Failed to get video call history',
                'data'      =>  $th->getMessage()
            ], 400);
        }
    }

    public function checkRoom(Request $request)
    {
        try {
            $user                   = JWTAuth::parseToken()->authenticate();

            if ($request->get("tutorid")) {
                $query              = $request->get("tutorid");
                $data               = RoomVC::where("user_id", $user->id)
                                    ->where("tutor_id", $query)
                                    ->where("status", "open")
                                    ->where("duration_left", ">", 0)->first();

                if ($data) {
                    return response()->json([
                        'status'    => 'success',
                        'message'   => 'Room exist',
                        'data'  => $data
                    ]);
                } else {
                    return response()->json([
                        'status'    => 'failed',
                        'message'   => 'Room not exist'
                    ]);
                }
            } else {
                return response()->json([
                    'status'    => 'failed',
                    'message'   => 'Missing param'
                ]);
            }

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'message'   =>  'Failed to check video call room',
                'data'      =>  $th
            ]);
        }
    }

    public function updateDuration(Request $request, $id)
    {
        try {

            $room                   = RoomVC::findOrFail($id);
            $room->duration_left    = $room->duration_left - $request->input("duration_used");
            $room->save();

            return response()->json([
                'status'            =>  'success',
                'message'           =>  'Success updating video call duration',
                'data'              =>  $room
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'status'            =>  'failed',
                'message'           =>  'failed to update video call duration',
                'data'              =>  $th->getMessage()
            ]);
        }
    }
}
