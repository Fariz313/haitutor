<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReportIssue extends Model
{
    protected $table = 'report_issue';

    const DELETE_STATUS = array(
        "TRUE"  => 1,
        "FALSE" => 0
    );
}
