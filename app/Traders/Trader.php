<?php

namespace App\Traders;

use App\Trading\CurrencyPairRate;

abstract class Trader
{
    public $currencyPairCode;
    public $test;

    public function __construct($currencyPairCode, $test = false)
    {
        $this->currencyPairCode = $currencyPairCode;
        $this->test = $test;
    }

    abstract public function getDecision(CurrencyPairRate $rate);
}