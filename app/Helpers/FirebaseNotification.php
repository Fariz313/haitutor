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
            'Authorization'=> 'key=AAAAq5PEITQ:APA91bE9Z7KmH5BDUi_fQJ8KCId7g0hdfrW8tEVmhRHwR4l7AtVwKFiNKJc3oklbkcSAFRvFqipPPKKwarYwICVcHCti0_QdeDbduDcHX6_3KpuqgeMc4C6l5-4Kw0UNolt1SViVXFCh',
        ];

        try{
            $dataNotif = new Notification();
            $dataNotif->sender_id = JWTAuth::parseToken()->authenticate()->id;
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
    		
            $body = [
                'data' => [
                    "title" => $data["title"],
                    "message" => $data["message"],
                    "sender_id" => $data["sender_id"],
                    "target_id" => $data["target_id"],
                ],
                'to' => $data["token_recipient"]
            ];
    
            $response = Http::withHeaders($headers)->post('https://fcm.googleapis.com/fcm/send', $body);
    
            return $response;

        } catch(\Exception $e){
            return $e->getMessage();
        }
    }
}