<?php

namespace App\ExchangeMarkets;

use App\Trading\Currency;
use App\Trading\CurrencyPairRate;
use App\Trading\Order;
use Carbon\Carbon;

class ExmoTestExchangeMarket extends ExchangeMarket
{
    public function getCode(): string
    {
        return 'exmo_test';
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

    // возвращаем только одну пару, так как столько используем в эмуляциях
    public function getCurrenciesRates()
    {
        $exmoRate = CurrencyPairRate::getLast('exmo.BTC.USD');

        $rates = [];
        // возвращаем последнюю котировку exmo
        $rates[] = new CurrencyPairRate(
            'exmo_test.BTC.USD',
            $exmoRate['buy_price'],
            $exmoRate['sell_price'],
            $this->floorToMinute(date('U'))
        );

        return [
            'rates'   => $rates,
            'metrics' => '',
        ];
    }

    public function placeOrder(Order $order, $public_key, $secret_key)
    {
        $id = date('U') * rand(1, 1000);

        return $id;
    }

    public function getDoneOrderInfo($exmOrderID, $currency1Amount, $public_key, $secret_key)
    {
        $order = \App\Models\Order::where('id_at_exm', $exmOrderID)->first();

        // ордер должен быть выставлен не менее минуты назад
        if ($order->created_at > Carbon::now()->subMinute()) {
            return false;
        }

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