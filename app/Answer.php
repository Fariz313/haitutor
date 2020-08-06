<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $table = "answer";
    
    public function fileAsk()
    {
        return $this->hasMany('App\FileAsk','parent_id');
    }
}
