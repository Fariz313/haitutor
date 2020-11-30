<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = "payment_method";

    const PAYMENT_METHOD_STATUS = array(
        "DISABLED"  => 0,
        "ENABLED"   => 1
    );

    const PAYMENT_METHOD_DELETED_STATUS = array(
        "ACTIVE"    => 0,
        "DELETED"   => 1
    );

    public function paymentCategory()
    {
        return $this->hasOne('App\PaymentMethodCategory','id','id_payment_category');
    }

    public function availablePaymentProvider()
    {
        return $this->hasMany('App\PaymentMethodProvider','id_payment_method','id');
    }
}
