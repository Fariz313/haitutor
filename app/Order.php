<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App;

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

    const PAYMENT_PROVIDER = array(
        "DUITKU"    => "DUITKU",
        "MIDTRANS"  => "MIDTRANS",
        "TRIPAY"    => "TRIPAY"
    );

    const ORDER_STATUS = array(
        "COMPLETED" => "completed",
        "PENDING"   => "pending",
        "FAILED"    => "failed"
    );

    const ENVIRONMENT = array(
        "DEVELOPMENT"   => "0",
        "PRODUCTION"    => "1"
    );

    const IS_VA = array(
        "TRUE"  => "1",
        "FALSE" => "0"
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

    const NON_VA = array(
        "VC", "BK", "OV"
    );

    const PAYMENT = array(
        "DUITKU"    => array(
            "PRODUCTION"    => array(
                "MERCHANT_CODE" => "D4709",
                "MERCHANT_KEY"  => "842145221e885c65e84ecc91084e757e",
                "RETURN_URL"    => "https://haitutor.id/backend-educhat/api/callback",
                "CALLBACK_URL"  => "https://haitutor.id/backend-educhat/api/callback"
            ),
            "DEVELOPMENT"   => array(
                "MERCHANT_CODE" => "D7176",
                "MERCHANT_KEY"  => "e5739c71cb0ed538c749e127233e2c12",
                "RETURN_URL"    => "https://haitutor.id/backend-educhat-dev/api/callback",
                "CALLBACK_URL"  => "https://haitutor.id/backend-educhat-dev/api/callback"
            )
        ),
        "MIDTRANS"  => array(
            "PRODUCTION"    => "",
            "DEVELOPMENT"   => ""
        )
    );

    public function package()
    {
        return $this->hasOne('App\Package','id','package_id');
    }
    public function user()
    {
        return $this->hasOne('App\User','id','user_id');
    }
    public function payment_method()
    {
        return $this->hasOne('App\PaymentMethodProvider', 'id', 'method_id');
    }

    public static function getEnvironment()
    {
        if (App::environment("local")) {
            return 0;
        } else if (App::environment("production")) {
            return 1;
        }
    }

}
