<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail{

    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function isAdmin(){
        if ($this->group){
            if ($this->group->isAdminGroup()){
                return true;
            }
        }

        return false;
    }

    // связи с другими моделями
    public function group(){
        return $this->belongsTo('App\Models\UserGroups');
    }

    public function accounts(){
        return $this->hasMany('App\Models\ExchangeMarketUserAccount');
    }
}
