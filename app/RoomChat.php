<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoomChat extends Model
{
    protected $table    =   'room_chat';

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
    public function service_name()
    {
        return $this->morphMany("App\Rating", "service_name");
    }
}
