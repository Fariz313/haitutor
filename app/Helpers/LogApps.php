<?php
namespace App\Helpers;

// use App\Logs;

use App\Ebook;
use App\User;
use App\Package;
use App\TutorDetail;

class LogApps {

    const LOG_TYPE = array(
        "LOGIN"     => "login",
        "LOGOUT"    => "logout",
        "DETAIL"    => "detail",
        "UPDATE"    => "update"
    );

    const UPDATE_USER_TYPE = array(
        "UPDATE_PROFILE"    => 1,
        "RESET_PASSWORD"    => 2
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

    public static function ebookDetail($data) {
        $logData                = new \App\Logs();
        $logData->user_id       = $data["USER"]->id;
        $logData->user_ip       = $data["USER_IP"];
        $logData->table_name    = Ebook::class;
        $logData->log_type      = LogApps::LOG_TYPE["DETAIL"];
        $logData->message       = "User " . $data["USER"]->name . " melihat " . $data["EBOOK"]->name;
        $logData->after         = json_encode($data["EBOOK"]);
        $logData->save();

        return $data;
    }

    public static function tutorDetail($data) {
        $logData                = new \App\Logs();
        $logData->user_id       = $data["USER"]->id;
        $logData->user_ip       = $data["USER_IP"];
        $logData->table_name    = TutorDetail::class;
        $logData->log_type      = LogApps::LOG_TYPE["DETAIL"];
        $logData->message       = "User " . $data["USER"]->name . " melihat " . $data["TUTOR"]->name;
        $logData->after         = json_encode($data["TUTOR"]);
        $logData->save();

        return $data;
    }

    public static function packageDetail($data) {
        $logData                = new \App\Logs();
        $logData->user_id       = $data["USER"]->id;
        $logData->user_ip       = $data["USER_IP"];
        $logData->table_name    = Package::class;
        $logData->log_type      = LogApps::LOG_TYPE["DETAIL"];
        $logData->message       = "User " . $data["USER"]->name . " melihat " . $data["PACKAGE"]->name;
        $logData->after         = json_encode($data["PACKAGE"]);
        $logData->save();

        return $data;
    }

    public static function editUser($data, $type = UPDATE_USER_TYPE["UPDATE_PROFILE"]) {
        $logData                = new \App\Logs();
        $logData->user_id       = $data["USER"]->id;
        $logData->user_ip       = $data["USER_IP"];
        $logData->table_name    = User::class;
        $logData->log_type      = LogApps::LOG_TYPE["UPDATE"];
        if($type == UPDATE_USER_TYPE["UPDATE_PROFILE"]){
            $logData->message   = "User " . $data["USER"]->name . " melakukan perubahan profil";
        } else {
            $logData->message   = "User " . $data["USER"]->name . " melakukan reset password";
        }
        $logData->before        = json_encode($data["BEFORE"]);
        $logData->after         = json_encode($data["AFTER"]);
        $logData->save();

        return $data;
    }
}
