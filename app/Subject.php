<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Subject extends Model
{
    protected $table    =   'subject';
    
    public function getUnassignedSubject($tutor_id)
    {
        $result = DB::select('SELECT * FROM subject WHERE ID NOT IN ( SELECT tutor_subject.subject_id as subject_id FROM subject JOIN tutor_subject ON subject.id = tutor_subject.subject_id WHERE tutor_subject.user_id = ?)', [$tutor_id]);
        return $result;
    }

}
