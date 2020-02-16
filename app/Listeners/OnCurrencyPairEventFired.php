<?php

namespace App\Listeners;

use App\Events\CurrencyPairEventFired;
use App\Trading\Event;

class OnCurrencyPairEventFired
{
    public function handle(CurrencyPairEventFired $event)
    {
        Event::fireComplexEvents(
            $event->currencyPairCode,
            $event->eventType,
            $event->params,
            $event->buy_price,
            $event->sell_price
        );
    }
}