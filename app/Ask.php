<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ask extends Model
{
    protected $table = 'ask';

    public function fileAsk()
    {
        return $this->hasMany('App\FileAsk','parent_id');
    }
    public function answer()
    {
        return $this->hasMany('App\Answer');
    }
}
