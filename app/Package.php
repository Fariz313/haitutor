<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $table = 'package';

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
