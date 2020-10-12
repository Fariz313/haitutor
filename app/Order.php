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
        "MERCHANT_CODE" => "D7147",
        "MERCHANT_KEY"  => "c885da6a4bbf4b6af33dd42af80f0f5d",
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
        "MAYBANK_BA"    => "VA",
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
