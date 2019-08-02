<?php

namespace App\Models;

use App\ExchangeMarkets\ExchangeMarketFabric;
use Illuminate\Database\Eloquent\Model;

class Order extends Model{

    protected $table = 'orders';
    protected $fillable = [
        'currency_1_code',
        'currency_2_code',
        'basket_id',
        'exchange_market_id',
        'amount',
        'gained_amount',
        'price',
        'action',
        'id_at_exm',
        'done',
    ];

    public function check(){
        $exmClass = ExchangeMarketFabric::get($this->exchangeMarket()->first()->code);
        $account  = $this->basket->account;

        $doneOrder = $exmClass->getDoneOrderInfo($this->id_at_exm, $this->amount, $account->public_key, $account->secret_key);

        if ($doneOrder){
            // наполняем полученное количество данными от биржи, чтобы данные всегда были реальные // todo наверное нужно вынести, так как этот метод о проверке, а не о записи количества
            if (isset($doneOrder['gained_amount'])){
                $this->gained_amount = $doneOrder['gained_amount'];
                $this->save();
            }

            return true;
        }

        return false;
    }

    // области видимости
    public function scopeUndone($query){
        return $query->where('done', false);
    }

    public function scopeDone($query){
        return $query->where('done', true);
    }

    // связи с другими моделями
    public function basket(){
        return $this->belongsTo('App\Models\Basket', 'basket_id');
    }

    public function exchangeMarket(){
        return $this->belongsTo('App\Models\ExchangeMarket', 'exchange_market_id');
    }
}
