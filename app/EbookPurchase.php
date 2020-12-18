<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EbookPurchase extends Model
{
    protected $table = 'ebook_purchase';

    const EBOOK_PURCHASE_STATUS = array(
        "PENDING"   => 0,
        "SUCCESS"   => 1,
        "FAILED"    => 2
    );

    const EBOOK_PURCHASE_DELETED_STATUS = array(
        "DELETED"   => 1,
        "ACTIVE"    => 0
    );

    public function ebook()
    {
        return $this->hasOne('App\Ebook','id','ebook_id');
    }
    public function user()
    {
        return $this->hasOne('App\User','id','user_id');
    }
    public function payment_method()
    {
        return $this->hasOne('App\PaymentMethodProvider', 'id', 'method_id');
    }
}
