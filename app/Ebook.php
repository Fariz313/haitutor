<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ebook extends Model
{
    protected $table = 'ebook';

    const EBOOK_TYPE = array(
        "FREE"  => 0,
        "PAID"  => 1
    );

    const EBOOK_PUBLISHED_STATUS = array(
        "NOT_PUBLISHED"     => 0,
        "PUBLISHED"         => 1
    );

    const EBOOK_DELETED_STATUS = array(
        "ACTIVE"    => 0,
        "DELETED"   => 1
    );

    public function ebookCategory()
    {
        return $this->hasOne('App\EbookCategory','id','id_category');
    }

    public function ebookPublisher()
    {
        return $this->hasOne('App\User','id','id_publisher');
    }
}
