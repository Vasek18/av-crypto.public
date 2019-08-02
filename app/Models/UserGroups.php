<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGroups extends Model{

    protected $table = 'user_groups';
    protected $fillable = [
        'name',
        'code'
    ];
    public $timestamps = false;

    public static $adminGroupCode = 'admin';

    public function isAdminGroup(){
        return $this->code == static::$adminGroupCode;
    }

    // связи с другими моделями
    public function users(){
        return $this->hasMany('App\Models\User');
    }
}
