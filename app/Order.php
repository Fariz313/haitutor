<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = "order";

    const POS_STATUS = array(
        "DEBET" => 0,
        "KREDIT" => 1
    );

    const TYPE_CODE = array(
        "INTERNAL" => 0,
        "PAYMENT_GATEWAY" => 1
    );

    const DUITKU_ATTRIBUTES = array(
        "MERCHANT_CODE" => "D7176",
        "MERCHANT_KEY"  => "e5739c71cb0ed538c749e127233e2c12",
        "RETURN_URL"    => "https://haitutor.id/restfull_api/api/callback",
        "CALLBACK_URL"  => "https://haitutor.id/restfull_api/api/callback"
    );

    const PAYMENT_METHOD = array(
        "CREDIT_CARD"   => "VC",
        "BCA_KLIKPAY"   => "BK",
        "MANDIRI_VA"    => "M1",
        "PERMATA_VA"    => "BT",
        "CIMB_VA"       => "B1",
        "ATM_BERSAMA"   => "A1",
        "BNI_VA"        => "I1",
        "MAYBANK_VA"    => "VA",
        "RITEL"         => "FT",
        "OVO"           => "OV"
    );

    public function package()
    {
        return $this->hasOne('App\Package','id','package_id');
    }
    public function user()
    {
        return $this->hasOne('App\User','id','user_id');
    }

}
