<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentMethodCategory extends Model
{
    protected $table = "payment_method_category";

    const PAYMENT_CATEGORY_STATUS = array(
        "DISABLED"  => 0,
        "ENABLED"   => 1
    );

    const PAYMENT_CATEGORY_DELETED_STATUS = array(
        "ACTIVE"    => 0,
        "DELETED"   => 1
    );

    public function enabledPaymentMethod()
    {
        return $this->hasMany('App\PaymentMethod','id_payment_category','id');
    }

    public function paymentMethodProvider()
    {
        return $this->hasMany('App\PaymentMethodProvider');
    }
}
