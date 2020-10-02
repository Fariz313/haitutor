<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class FirebaseNotification {
    public static function pushNotification($data) {
        $headers = [
            'Content-type' => 'application/json',
            'Authorization'=> 'key=AAAAq5PEITQ:APA91bE9Z7KmH5BDUi_fQJ8KCId7g0hdfrW8tEVmhRHwR4l7AtVwKFiNKJc3oklbkcSAFRvFqipPPKKwarYwICVcHCti0_QdeDbduDcHX6_3KpuqgeMc4C6l5-4Kw0UNolt1SViVXFCh',
        ];

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
    }
}