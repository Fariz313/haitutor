<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EbookOrder extends Model
{
    protected $table = 'ebook_order';

    const EBOOK_ORDER_STATUS = array(
        "PENDING"       => 0,
        "ACTIVE"        => 1,
        "NON_ACTIVE"    => 2
    );

    const EBOOK_ORDER_DELETED_STATUS = array(
        "DELETED"   => 1,
        "ACTIVE"    => 0
    );

    public function customer()
    {
        return $this->hasOne('App\User','id','id_customer');
    }

    public function detail()
    {
        return $this->hasMany('App\EbookOrderDetail','id_order','id');
    }
}
