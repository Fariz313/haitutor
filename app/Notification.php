<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notification';

    const CHANNEL_NOTIF_NAMES = array(
        0 => "CHANNEL_CHAT",
        1 => "CHANNEL_VIDEO_CALL",
        2 => "CHANNEL_ROOM",
        3 => "CHANNEL_VERIFICATION"
    );
}
