<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoomVC extends Model
{

    const AGORA_APP_ID = "9dca2f48c8174aa7a0a172cc235a9960";
    const AGORA_APP_CERFITICATE = "4e424f6ab3f242cb995a881deedb18fa";

    protected $table = "room_vc";

    public function user()
    {
        return $this->belongsTo('App\User');
    }
    public function tutor()
    {
        return $this->belongsTo('App\User','tutor_id','id');
    }
    public function history_vc()
    {
        return $this->hasMany('App\HistoryVC','room_id');
    }

    public function service_name()
    {
        return $this->morphMany("App\Rating", "service_name");
    }

}
