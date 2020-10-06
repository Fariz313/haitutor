<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use JWTAuth;
use App\Chat;
use App\RoomChat;
use Carbon\Carbon;
use FCM;


class ChatController extends Controller
{
    public function store(Request $request, $roomkey)
    {
        try{
            $user               = JWTAuth::parseToken()->authenticate();
    		$validator          = Validator::make($request->all(), [
    			'text'          => 'max:2000',
				'file'	        => 'file',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}
            $requestCount           =   0;
            $data                   = new Chat();
            if ($request->input('text')) {
                $data->text         = $request->input('text');
                $requestCount       +=   1;
            }
            $data->user_id          = $user->id;
            $data->room_key         = $roomkey;
            if($request->hasFile('file')){     
                try {
                    $requestCount   +=   1;
                    $file           = $request->file('file');
                    $tujuan_upload  = 'temp/chat';
                    $data->save();
                    $file_name      = $user->id.'_'.$file->getClientOriginalName().'_'.Str::random(3).'.'.$file->getClientOriginalExtension();
                    $file->move($tujuan_upload,$file_name);
                    $data->file     =   $tujuan_upload.'/'.$file_name;
                } catch (\Throwable $th) {
                    return response()->json([   
                        'status'	=> 'failed',
                        'message'	=> 'failed adding ask with image'
                    ], 501);
                }
            }
            if($requestCount>0){
                if($data->save()){
                    $room = RoomChat::where('room_key',$roomkey)->first();
                    $room->last_message_at = $data->created_at;
                    $room->save();

                    $target = $room->user;
                    $sender = $room->tutor;
                    if($user->id == $room->user_id){
                        $target = $room->tutor;
                        $sender = $room->user;
                    }

                    $dataNotif = [
                        "title" => "HaiTutor",
                        "message" => "Pesan Masuk dari " . $sender->name,
                        "sender_id" => $sender->id,
                        "target_id" => $target->id,
                        'token_recipient' => $target->firebase_token,
                        'save_data' => false
                    ];
                    $responseNotif = FCM::pushNotification($dataNotif);

                    return response()->json([
                        'status'	=> 'succes',
                        'message'	=> 'Success adding chat',
                        'notif'     => $responseNotif
                    ], 201);
                }
            }else{
                return response()->json([
                    'status'	=> 'failed',
                    'message'	=> 'no input'
                ], 400);
            }
            
            
        } catch(\Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => "Message not sended"
            ],500);
        }
    }

    public function destroy($roomkey,$id)
    {
        try {
            $data               =   Chat::where('room_key',$roomkey)->find($id);
            $data->deleted_at   =   Carbon::now();
            $data->save();
            return reponse()->json([
                'status'    =>  'success',
                'message'   =>  'Chat is Deleted'],200);
        } catch (\Throwable $th) {
            return reponse()->json([
                'status'    =>  'success',
                'message'   =>  'Chat is Deleted'],500);
        }
    }
}