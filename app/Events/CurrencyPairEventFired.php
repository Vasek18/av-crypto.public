<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CurrencyPairEventFired
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $currencyPairCode;
    public $eventType;
    public $buy_price;
    public $sell_price;
    public $timestamp;
    public $params;

    public function __construct(
        $currencyPairCode,
        $eventType,
        $buy_price,
        $sell_price,
        $timestamp,
        $params
    ) {
        $this->currencyPairCode = $currencyPairCode;
        $this->eventType = $eventType;
        $this->buy_price = $buy_price;
        $this->sell_price = $sell_price;
        $this->timestamp = $timestamp;
        $this->params = $params;
    }
}
