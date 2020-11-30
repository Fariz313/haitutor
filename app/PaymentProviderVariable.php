<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentProviderVariable extends Model
{
    protected $table = "payment_provider_variable";

    const PAYMENT_PROVIDER_VAR_DELETED_STATUS = array(
        "ACTIVE"    => 0,
        "DELETED"   => 1
    );
}
