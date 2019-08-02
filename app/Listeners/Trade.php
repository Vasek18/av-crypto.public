<?php

namespace App\Listeners;

use App\Events\CurrencyPairRateChanged;
use App\Models\Basket;
use App\Models\Metrics\Metrics;
use App\Models\TraderDecision as DesicionInDB;
use App\Traders\AlwaysBuyTrader;
use App\Traders\AveragePriceTrader;
use App\Traders\OtstupTrader;
use App\Traders\Trader;
use App\Traders\TraderDecision;

class Trade
{
    public function handle(CurrencyPairRateChanged $event)
    {
        // трейдер отступов
        $this->tradeByOneTrader(OtstupTrader::class, $event);

        // трейдер перегиба среднего
        $this->tradeByOneTrader(AveragePriceTrader::class, $event);

        // трейдер, который всегда советует покупать, исполняется только на тесте
        if ('testing' === env('APP_ENV')) {
            $this->tradeByOneTrader(AlwaysBuyTrader::class, $event);
        }
    }

    /**
     * @param string $traderClass
     * @param CurrencyPairRateChanged $event
     */
    public function tradeByOneTrader($traderClass, $event)
    {
        /** @var Trader $trader */
        $trader = new $traderClass($event->currencyPairCode);
        $decision = $trader->getDecision($event->rate);
        if ($decision) {
            $this->triggerBaskets($event->currencyPairID, $event->serverTimestamp, $decision);
            $this->saveDecisionInDB($event->currencyPairID, $decision);
        }
    }

    public function saveDecisionInDB($currencyPairID, TraderDecision $decision)
    {
        $decisionCode = $decision->action == SELL_ACTION_CODE ? 'S' : 'B';

        DesicionInDB::create(
            [
                'currency_pair_id' => $currencyPairID,
                'trader_code'      => $decision->traderCode,
                'decision'         => $decisionCode,
                'timestamp'        => $decision->timestamp,
            ]
        );
    }

    public function triggerBaskets($currencyPairID, $startTimestamp, TraderDecision $decision)
    {
        $baskets = static::getBaskets($currencyPairID, $decision->action, $decision->traderCode);

        foreach ($baskets as $basket) {
            /** @var Basket $basket */
            $order = $basket->createOrder($decision->price);

            if ($order) {
                Metrics::log(
                    'time_from_rates_check_to_order_creation',
                    (date('U') - $startTimestamp)
                );
            }
        }
    }

    // static, потому что так легче тестировать
    public static function getBaskets($currencyPairID, $nextAction, $strategy)
    {
        $baskets = Basket::active()->where('currency_pair_id', $currencyPairID)
            ->where('next_action', $nextAction)
            ->where('strategy', $strategy)
            ->get();

        return $baskets;
    }
}