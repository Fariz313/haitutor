<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EbookLibrary extends Model
{
    protected $table = 'ebook_library';

    const EBOOK_LIBRARY_STATUS = array(
        "ACTIVE"        => 1,
        "NON_ACTIVE"    => 0
    );
}
