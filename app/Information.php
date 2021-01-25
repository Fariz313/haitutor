<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Information extends Model
{
    protected $table = 'information';

    const ATRRIBUTE_NAME = array(
        "MINIMUM_PACKAGE_PRICE" => "minimum_package_price"
    );
}
