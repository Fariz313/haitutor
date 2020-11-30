<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $table = "otp";

    const OTP_PAYLOAD = array (
        "OTP" => "otp",
        "TITLE" => "otp_title",
        "TYPE" => "otp_type",
        "NO_TELP" => "no_telp",
        "ALAMAT" => "alamat",
        "ACTION_USER" => "otp_action_user",
        "MESSAGE" => "otp_message"
    );

    const OTP_TYPE = array (
        "VERIFY_EMAIL" => "verify_email",
        "RESET_PASSWORD" => "reset_password"
    );
}
