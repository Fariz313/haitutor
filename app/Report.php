<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    const ReportStatus = [
        "NEW"  => 0,
        "READ" => 1
    ];

    protected $table = 'report';

    public function reportIssue()
    {
        return $this->hasOne('App\ReportIssue', 'id', 'issue_id');
    }

    public function sender()
    {
        return $this->hasOne('App\User', 'id', 'sender_id');
    }

    public function target()
    {
        return $this->hasOne('App\User', 'id', 'target_id');
    }

}
