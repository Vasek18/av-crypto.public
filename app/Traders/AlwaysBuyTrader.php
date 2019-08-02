<?php

namespace App\Traders;

use App\Trading\CurrencyPairRate;

class AlwaysBuyTrader extends Trader
{
    public function getDecision(CurrencyPairRate $rate)
    {
        return new TraderDecision($rate->buy_price, $rate->timestamp, BUY_ACTION_CODE, 'AlwaysBuyTrader');
    }
}
