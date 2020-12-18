<?php
namespace App\Helpers;

// use App\Logs;
use App\User;

class LogApps {

    const LOG_TYPE = array(
        "LOGIN"     => "login",
        "LOGOUT"    => "logout"
    );

    public static function login($data) {
        $logData                = new \App\Logs();
        $logData->user_id       = $data["USER"]->id;
        $logData->user_ip       = $data["USER_IP"];
        $logData->table_name    = User::class;
        $logData->log_type      = LogApps::LOG_TYPE["LOGIN"];
        $logData->message       = "User " . $data["USER"]->name . " berhasil login";
        $logData->after         = json_encode($data["USER"]);
        $logData->save();

        return $data;
    }

    public static function logout($data) {
        $logData                = new \App\Logs();
        $logData->user_id       = $data["USER"]->id;
        $logData->user_ip       = $data["USER_IP"];
        $logData->table_name    = User::class;
        $logData->log_type      = LogApps::LOG_TYPE["LOGOUT"];
        $logData->message       = "User " . $data["USER"]->name . " berhasil logout";
        $logData->after         = json_encode($data["USER"]);
        $logData->save();

        return $data;
    }
}
