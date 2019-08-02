<?php

namespace App\ExchangeMarkets;

use App\Models\ExchangeMarketCurrencyPair;
use App\Trading\Currency;
use App\Trading\CurrencyPairRate;
use App\Trading\Order;

class TestExchangeMarket extends ExchangeMarket
{
    public function getCode(): string
    {
        return 'test';
    }

    public function connect($public_key, $secret_key): bool
    {
        return $public_key == 'test' && $secret_key == 'test';
    }

    public function getBalances($public_key, $secret_key)
    {
        return [
            [
                'currency' => new Currency('BTC'),
                'amount'   => 2.50005000,
            ],
            [
                'currency' => new Currency('USD'),
                'amount'   => 1000.50005000,
            ],
        ];
    }

    public function getCurrenciesRates()
    {
        $rates = [];

        $startAmounts = [
            'BTC_USD' => 9000,
            'BTC_RUB' => 45000,
            'ETH_BTC' => 0.02,
            'ETH_USD' => 222,
        ];

        $pairsInDb = ExchangeMarketCurrencyPair::where('exchange_market_id', 1)->get(); // todo хардкод биржи

        /** @var ExchangeMarketCurrencyPair $pair */
        foreach ($pairsInDb as $pair) {
            $lastRate = CurrencyPairRate::getLast($pair->code);

            $rangePercent = 10;
            if ($lastRate) {
                $buyPrice = $lastRate['buy_price'] * ((100 - ($rangePercent / 2) + rand(0, $rangePercent)) / 100);
                $sellPrice = $lastRate['sell_price'] * ((100 - ($rangePercent / 2) + rand(0, $rangePercent)) / 100);
            } else {
                $buyPrice = $startAmounts[$pair->currency_1_code.'_'.$pair->currency_2_code];
                $sellPrice = $startAmounts[$pair->currency_1_code.'_'.$pair->currency_2_code];
            }

            $rates[] = new CurrencyPairRate(
                $pair->code,
                $buyPrice,
                $sellPrice,
                $this->floorToMinute(date('U'))
            );
        }

        return [
            'rates'   => $rates,
            'metrics' => '',
        ];
    }

    public function placeOrder(Order $order, $public_key, $secret_key)
    {
        if (defined('TEST_EXCHANGE_MARKET_FAIL') && TEST_EXCHANGE_MARKET_FAIL == true) {
            return false;
        }

        $id = date('U') * rand(1, 1000);

        return $id;
    }

    public function getDoneOrderInfo($exmOrderID, $currency1Amount, $public_key, $secret_key)
    {
        if (defined('TEST_EXCHANGE_MARKET_FAIL') && TEST_EXCHANGE_MARKET_FAIL == true) {
            return false;
        }

        $order = \App\Models\Order::where('id_at_exm', $exmOrderID)->first();

        return [
            'gained_amount' => $order->gained_amount,
        ];
    }

    /**
     * Получение списка валют и их лимитов
     *
     * @param integer $idInDB
     *
     * @return mixed
     */
    function updatePairsAndSettings($idInDB = null)
    {
        // do nothing
    }

    /**
     * Получение стаканов и объёмов торгов
     *
     * @return mixed
     */
    function getOrderBook()
    {
        // do nothing
    }
}