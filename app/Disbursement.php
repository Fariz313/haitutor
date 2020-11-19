<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Disbursement extends Model
{
    const DisbursementStatus = [
        "PENDING"   => 0,
        "ACCEPTED"  => 1,
        "REJECTED"  => 2
    ];

    protected $table = 'disbursement';
}
