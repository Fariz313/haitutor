<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Logs extends Model
{
    protected $table = 'logs';
    
    public function user()
    {
        return $this->hasOne('App\User','id','user_id');
    }
}
