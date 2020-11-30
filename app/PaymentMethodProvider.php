<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentMethodProvider extends Model
{
    protected $table = "payment_method_provider";

    const PAYMENT_METHOD_PROVIDER_STATUS = array(
        "DISABLED"  => 0,
        "ENABLED"   => 1
    );

    const PAYMENT_METHOD_PROVIDER_DELETED_STATUS = array(
        "ACTIVE"    => 0,
        "DELETED"   => 1
    );

    public function paymentMethod()
    {
        return $this->hasOne('App\PaymentMethod', 'id', 'id_payment_method');
    }

    public function paymentProvider()
    {
        return $this->hasOne('App\PaymentProvider', 'id_payment_provider');
    }
}
