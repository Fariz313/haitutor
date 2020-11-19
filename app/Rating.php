<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $table = 'rating';

    public function sender()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }

    public function receiver()
    {
        return $this->hasOne('App\User', 'id', 'tutor_id');
    }
}
