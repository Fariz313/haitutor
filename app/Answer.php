<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $table = 'answer';

    const ANSWER_DELETED_STATUS = array(
        "ACTIVE"    => 0,
        "DELETED"   => 1
    );

    public function documents()
    {
        return $this->hasMany('App\AnswerDoc','id_answer','id');
    }
}
