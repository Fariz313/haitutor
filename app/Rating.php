<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $table = 'rating';

    const SERVICEABLE_TYPE = array(
        "CHAT"      => 'chat',
        "VIDEOCALL" => 'videocall',
        "EBOOK"     => 'ebook'
    );

    public function sender()
    {
        return $this->hasOne('App\User', 'id', 'sender_id');
    }

    public function target()
    {
        return $this->hasOne('App\User', 'id', 'target_id');
    }

    public function serviceable()
    {
        return $this->morphTo();
    }
}
