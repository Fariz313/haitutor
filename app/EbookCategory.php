<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EbookCategory extends Model
{
    protected $table = 'ebook_category';

    const EBOOK_CATEGORY_DELETED_STATUS = array(
        "ACTIVE"    => 0,
        "DELETED"   => 1
    );
}
