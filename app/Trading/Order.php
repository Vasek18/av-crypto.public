<?php

namespace App\Trading;

class Order{

    public $currency1;
    public $currency2;
    public $amount;
    public $price;
    public $action;
    public $idAtExm;

    public function __construct(Currency $currency1, Currency $currency2, $amount, $price, $action, $idAtExm = false){
        $this->currency1 = $currency1;
        $this->currency2 = $currency2;
        $this->amount    = $amount;
        $this->price     = $price;
        $this->action    = $action;
        $this->idAtExm   = $idAtExm;
    }

}