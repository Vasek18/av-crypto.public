<?php

namespace App\Traders;

class TraderDecision
{

    public $price;
    public $timestamp;
    public $action;

    public function __construct($price, $timestamp, $action, $traderCode)
    {
        $this->price = $price;
        $this->timestamp = $timestamp;
        $this->action = $action;
        $this->traderCode = $traderCode;
    }

}