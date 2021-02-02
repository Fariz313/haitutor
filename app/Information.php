<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Information extends Model
{
    protected $table = 'information';

    const ATRRIBUTE_NAME = array(
        "MINIMUM_PACKAGE_PRICE"         => "minimum_package_price",
        "DEFAULT_ICON_USER"             => "default_icon_user",
        "DEFAULT_ICON_SUBJECT"          => "default_icon_subject",
        "DEFAULT_ICON_PAYMENT_METHOD"   => "default_icon_payment_method"
    );

    const TYPE_ICON = array(
        "USER"              => "user",
        "SUBJECT"           => "subject",
        "PAYMENT_METHOD"    => "payment_method"
    );
}
