<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = "payment_method";

    public function paymentCategory()
    {
        return $this->hasOne('App\PaymentMethodCategory','id','id_payment_category');
    }

    public function availablePaymentProvider()
    {
        return $this->hasMany('App\PaymentMethodProvider','id_payment_method','id');
    }
}
