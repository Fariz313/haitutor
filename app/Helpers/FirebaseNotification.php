<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use JWTAuth;
use App\Notification;
use Illuminate\Http\Request;

class FirebaseNotification {
    public static function pushNotification($data) {
        $headers = [
            'Content-type' => 'application/json',
            'Authorization'=> 'key=AAAAFSp2p7U:APA91bHiD1lq7ReClUI7eL1_96C-bxw3yGd8iplnExkHGP3fkZ5HtbJnu-kPoKwzuAxciIUzYDpQpnja8cGm1JDMUQANPIYQHb9m56HluJVHj-pxkvP8_f6owIaOSZ7rESzJowA5qibz',
        ];

        try{
            if($data["save_data"]){
                $dataNotif = new Notification();
                if($data["sender_id"] == 0){
                    $dataNotif->sender_id = 0;
                } else {
                    $dataNotif->sender_id = JWTAuth::parseToken()->authenticate()->id;
                }
                $dataNotif->target_id = $data["target_id"];
                $dataNotif->message = $data["message"];
                $dataNotif->status = 0;
                if(array_key_exists("action", $data)){
                    $dataNotif->action = $data["action"];
                }
                if(array_key_exists("image", $data)){
                    $dataNotif->image = $data["image"];
                }
                $dataNotif->save();
            }

            if ($data["channel_name"] == Notification::CHANNEL_NOTIF_NAMES[1]) {
                $body = [
                    'data' => [
                        "title" => $data["title"],
                        "message" => $data["message"],
                        "sender_id" => $data["sender_id"],
                        "target_id" => $data["target_id"],
                        "channel_name"  => $data["channel_name"],
                        "duration"      => $data["duration"],
                    ],
                    'to' => $data["token_recipient"]
                ];
            } else if ($data["channel_name"] == Notification::CHANNEL_NOTIF_NAMES[4]) {
                $body = [
                    'data' => [
                        "title" => $data["title"],
                        "message" => $data["message"],
                        "sender_id" => $data["sender_id"],
                        "target_id" => $data["target_id"],
                        "channel_name"  => $data["channel_name"],
                        "amount"      => $data["amount"],
                    ],
                    'to' => $data["token_recipient"]
                ];
            } else if($data["channel_name"] == Notification::CHANNEL_NOTIF_NAMES[5] || $data["channel_name"] == Notification::CHANNEL_NOTIF_NAMES[6] || $data["channel_name"] == Notification::CHANNEL_NOTIF_NAMES[4]) {
                $body = [
                    'data' => [
                        "title" => $data["title"],
                        "message" => $data["message"],
                        "sender_id" => $data["sender_id"],
                        "target_id" => $data["target_id"],
                        "channel_name"  => $data["channel_name"],
                        "room_vc"   => $data["room_vc"]
                    ],
                    'to' => $data["token_recipient"]
                ];
            }else {
                $body = [
                    'data' => [
                        "title" => $data["title"],
                        "message" => $data["message"],
                        "sender_id" => $data["sender_id"],
                        "target_id" => $data["target_id"],
                        "channel_name"  => $data["channel_name"],
                    ],
                    'to' => $data["token_recipient"]
                ];
            }

            $response = Http::withHeaders($headers)->post('https://fcm.googleapis.com/fcm/send', $body);

            return $response;

        } catch(\Exception $e){
            return $e->getMessage();
        }
    }
}
