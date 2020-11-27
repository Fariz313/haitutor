<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $table = 'rating';

    public function sender()
    {
        return $this->hasOne('App\User', 'id', 'sender_id');
    }

    public function target()
    {
        return $this->hasOne('App\User', 'id', 'target_id');
    }
}
