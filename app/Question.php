<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $table = 'question';

    const EXPIRED_DAYS = 2;

    const QUESTION_DELETED_STATUS = array(
        "ACTIVE"    => 0,
        "DELETED"   => 1
    );

    public function documents()
    {
        return $this->hasMany('App\QuestionDoc','id_question','id');
    }
}
