<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EbookOrderDetail extends Model
{
    protected $table = 'ebook_order_detail';
    
    public function redeem()
    {
        return $this->hasOne('App\EbookRedeem','id','id_redeem');
    }

    public function ebook()
    {
        return $this->hasOne('App\Ebook','id','id_ebook');
    }
}
