<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoomVC extends Model
{
    
    protected $table = "room_vc";

    public function user()
    {
        return $this->belongsTo('App\User');
    }
    public function tutor()
    {
        return $this->belongsTo('App\User','tutor_id','id');
    }

}