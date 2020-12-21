<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = "menu";

    const IS_MENU = array(
        "MENU"      => 1,
        "NON_MENU"  => 0
    );

    const STATUS_MENU_DELETED = array(
        "ACTIVE"    => 1,
        "DELETED"   => 0
    );

    const ACTION_METHOD = array(
        "GET"       => "GET",
        "POST"      => "POST",
        "PUT"       => "PUT",
        "DELETE"    => "DELETE"
    );
}
