<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoomChat extends Model
{
    protected $table    =   'room_chat';

    const ROOM_STATUS = array(
        "OPEN"      => 'open',
        "CLOSED"    => 'closed'
    );

    const ROOM_DELETED_STATUS = array(
        "ACTIVE"    => 0,
        "DELETED"   => 1
    );

    public function chat()
    {
        return $this->hasMany('App\Chat','room_key','room_key');
    }
    public function user()
    {
        return $this->belongsTo('App\User');
    }
    public function tutor()
    {
        return $this->belongsTo('App\User','tutor_id','id');
    }

    public function ratings()
    {
        return $this->morphMany(Rating::class, "serviceable");
    }
}
