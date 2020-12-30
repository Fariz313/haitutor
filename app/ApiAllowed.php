<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApiAllowed extends Model
{
    protected $table = "view_api_allowed";
    
    const ALLOWED_STATUS = array(
        "ALLOWED"       => 1,
        "NOT_ALLOWED"   => 0
    );
}
