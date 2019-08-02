<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// todo вынести в редис
class TraderDecision extends Model
{
    protected $table      = 'traders_decisions';
    protected $fillable   = [
        'currency_pair_id',
        'trader_code',
        'decision',
        'timestamp',
    ];
    public    $timestamps = false;
}
