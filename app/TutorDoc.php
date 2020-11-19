<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TutorDoc extends Model
{
    const TutorDocStatus = [
        "VERIFIED"      => 'verified',
        "UNVERIFIED"    => 'unverified',
        "PENDING"       => 'pending'
    ];

    const TutorDocType = [
        "IJAZAH"        => 'ijazah',
        "CV"            => 'cv',
        "SERTIFIKAT"    => 'certificate',
        "KTP"           => 'ktp',
        "NO_REKENING"   => 'no_rekening',
        "OTHER"         => 'other'
    ];

    protected $table    =   'tutor_doc';
}
