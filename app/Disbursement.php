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

    const DisbursementRequirementError = [
        "NO_ERROR"      => 0,
        "NIK"           => 1,
        "NO_REK"        => 2,
        "KTP"           => 3,
        "BUKU_REKENING" => 4
    ];

    protected $table = 'disbursement';
}
