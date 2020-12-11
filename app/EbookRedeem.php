<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EbookRedeem extends Model
{
    protected $table = 'ebook_redeem';

    const EBOOK_REDEEM_STATUS = array(
        "PENDING"       => 0,
        "ACTIVE"        => 1,
        "NON_ACTIVE"    => 2
    );

    const EBOOK_REDEEM_DELETED_STATUS = array(
        "DELETED"   => 1,
        "ACTIVE"    => 0
    );

    public function customer()
    {
        return $this->hasOne('App\User','id','id_customer');
    }

    public function detail()
    {
        return $this->hasMany('App\EbookRedeemDetail','id_redeem','id');
    }
}
