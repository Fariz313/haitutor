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
}
