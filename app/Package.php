<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $table = 'package';

    const PACKAGE_DELETED_STATUS = array(
        "ACTIVE"    => 0,
        "DELETED"   => 1,
    );

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
