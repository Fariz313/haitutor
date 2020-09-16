<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = "order";

    public function package()
    {
        return $this->hasOne('App\Package','id','package_id');
    }
    public function user()
    {
        return $this->belongTo('App\User');
    }
    
}
