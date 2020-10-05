<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HistoryVC extends Model
{
    
    protected $table = "history_vc";

    public function user()
    {
        return $this->belongsTo('App\User');
    }
    public function tutor()
    {
        return $this->belongsTo('App\User','tutor_id','id');
    }

}