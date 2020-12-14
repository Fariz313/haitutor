<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EbookRedeemHistory extends Model
{
    protected $table = 'ebook_redeem_history';
    
    public function redeem_detail()
    {
        return $this->hasOne('App\EbookRedeemDetail','id','id_redeem_detail');
    }

    public function user()
    {
        return $this->hasOne('App\Users','id','id_user');
    }
}
