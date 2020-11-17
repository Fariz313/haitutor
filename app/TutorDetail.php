<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TutorDetail extends Model
{
    const TutorStatus = [
        "VERIFIED"      => 'verified',
        "UNVERIFIED"    => 'unverified',
        "PENDING"       => 'pending'
    ];

    protected $table    =   'tutor_detail';
    
}
