<?php

namespace App\Trading;

class CurrencyPairMetric
{
    public $currency_1;
    public $currency_2;
    public $code;
    public $value;
    public $timestamp;

    public function __construct(Currency $currency_1, Currency $currency_2, $code, $value, $timestamp)
    {
        $this->currency_1 = $currency_1;
        $this->currency_2 = $currency_2;
        $this->code = $code;
        $this->value = $value;
        $this->timestamp = $timestamp;
    }
}