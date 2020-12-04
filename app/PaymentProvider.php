<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentProvider extends Model
{
    protected $table = "payment_provider";

    const PAYMENT_PROVIDER_STATUS = array(
        "DISABLED"  => 0,
        "ENABLED"   => 1
    );

    const PAYMENT_PROVIDER_DELETED_STATUS = array(
        "ACTIVE"    => 0,
        "DELETED"   => 1
    );

    public function paymentMethod()
    {
        return $this->hasMany('App\PaymentMethodProvider','id_payment_provider','id');
    }

    public function providerVariables()
    {
        return $this->hasMany('App\PaymentProviderVariable','id_payment_provider','id');
    }

    public function providerVariablesDevelopment()
    {
        return $this->hasMany('App\PaymentProviderVariable','id_payment_provider','id');
    }

    public function providerVariablesProduction()
    {
        return $this->hasMany('App\PaymentProviderVariable','id_payment_provider','id');
    }
}
