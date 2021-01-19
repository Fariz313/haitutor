<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Logs extends Model
{
    protected $table = 'logs';

    const DISPLAY_LOG = array(
        "WEEK"  => 'W',
        "MONTH" => 'M',
        "YEAR"  => 'Y'
    );

    public function user()
    {
        return $this->hasOne('App\User','id','user_id');
    }
}
