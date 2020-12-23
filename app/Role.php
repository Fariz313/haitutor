<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = "role";

    const ROLE = array(
        "ADMIN"     => 1,
        "TUTOR"     => 2,
        "STUDENT"   => 3,
        "PUBLISHER" => 4,
        "SCHOOL"    => 5,
        "MARKETING" => 6,
        "COMPANY"   => 7
    );

    const ROLE_NAME = array(
        "ADMIN"     => "Administrator",
        "TUTOR"     => "Tutor",
        "STUDENT"   => "Student",
        "PUBLISHER" => "Publisher",
        "SCHOOL"    => "School",
        "MARKETING" => "Marketing",
        "COMPANY"   => "Company"
    );
}
