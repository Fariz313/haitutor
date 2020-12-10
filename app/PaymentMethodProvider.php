<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentMethodProvider extends Model
{
    // Kolom 'status'       untuk status Enable/Disable Payment Method pada Provider tertentu
    // Kolom 'isActive'     untuk mengetahui Payment Method mana yang AKTIF (ditampilkan di mobile) saat ini (HANYA ADA SATU untuk masing-masing Payment Method)
    // Kolom 'isDeleted'    untuk mengetahui Payment Method yang TERSEDIA pada Provider tertentu
    
    protected $table = "payment_method_provider";

    const PAYMENT_METHOD_PROVIDER_STATUS = array(
        "DISABLED"  => 0,
        "ENABLED"   => 1
    );

    const PAYMENT_METHOD_PROVIDER_ACTIVE_STATUS = array(
        "NON_ACTIVE"    => 0,
        "ACTIVE"        => 1
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
        return $this->hasOne('App\PaymentProvider', 'id', 'id_payment_provider');
    }

    public function paymentMethodProviderVariable()
    {
        return $this->hasMany('App\PaymentMethodProviderVariable', 'id_payment_method_provider', 'id');
    }
}
