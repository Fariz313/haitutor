<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoomAsk extends Model
{
    protected $table = 'room_ask';

    const ROOM_ASK_STATUS = array(
        "OPEN"      => 0,
        "ACCEPTED"  => 1,
        "REJECTED"  => 2
    );

    const ROOM_ASK_DELETED_STATUS = array(
        "ACTIVE"    => 0,
        "DELETED"   => 1
    );
}
