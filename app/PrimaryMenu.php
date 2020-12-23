<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PrimaryMenu extends Model
{
    protected $table = 'view_primary_menu';

    public function subMenu()
    {
        return $this->hasMany('App\SubMenu', 'id_parent_menu', 'id');
    }
    
}
