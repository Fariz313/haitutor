<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use JWTAuth;
use App\Chat;
use App\RoomChat;
use App\Notification;
use Carbon\Carbon;
use FCM;
use App\Helpers\CloudKilatHelper;
use App\Helpers\GoogleCloudStorageHelper;

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
                    'status'    => 'failed validate',
                    'error'     => $validator->errors()
                ],400);
            }

            $data                   = new Chat();
            $message                = "";

            if ($request->input('text')) {
                $data->text         = $request->input('text');
                $message            = $request->input('text');
            }

            $data->user_id          = $user->id;
            $data->room_key         = $roomkey;
            if($request->hasFile('file')){
                try {
                    $file           = $request->file('file');

                    if ($request->input('text')) {
                        $message    = "[Photo] " . $request->input('text');
                    } else {
                        $message    = "[Photo] Photo";
                    }

                    $file           = GoogleCloudStorageHelper::put($request->file('file'), "/photos/chat/", 'image', $user->id);
                    $data->file     = $file;
                    $data->text     = $message;

                } catch (\Throwable $th) {
                    return response()->json([
                        'status'	=> 'failed',
                        'message'	=> 'failed adding ask with image',
                        "data"      => $th->getMessage()
                    ], 501);
                }
            }

            if($data->save()){
                $room = RoomChat::where('room_key',$roomkey)->first();

                $target = $room->user;
                $sender = $room->tutor;
                if($user->id == $room->user_id){
                    $target = $room->tutor;
                    $sender = $room->user;
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
                    'save_data' => false
                ];
                $responseNotif = FCM::pushNotification($dataNotif);

                return response()->json([
                    'status'	=> 'Success',
                    'message'	=> 'Success adding chat',
                    'data'      => array(
                        "chat_data" => $data,
                        "notif"     => json_decode($responseNotif),
                        "url_image" => $data->file
                    )
                ], 201);
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

    public function updateReadedMessage($roomKey)
    {
        try {

            $status     = "";
            $message    = "";

            $user       = JWTAuth::parseToken()->authenticate();
            $room_chat  = RoomChat::where("room_key", $roomKey)->firstOrFail();

            if ($user->id != $room_chat->last_sender) {
                $room_chat->last_message_readed     = "true";
                $room_chat->last_message_readed_at  = Carbon::now();
                $room_chat->save();

                $status     = "success";
                $message    = "Message readed !";

            } else {
                $status     = "failed";
                $message    = "It's your chat";
            }

            return response()->json([
                'status'    =>  $status,
                'message'   =>  $message
            ],200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'success',
                'data'      => $th->getMessage(),
                'message'   =>  'Failed to read message'],500);
        }
    }
}
