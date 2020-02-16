<?php

namespace App\Jobs;

use App\CurrencyPairsMetrics\BuyAmount;
use App\CurrencyPairsMetrics\SellQuantity;
use App\CurrencyPairsMetrics\Spread;
use App\ExchangeMarkets\ExchangeMarketFabric;
use App\Models\ExchangeMarket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class UpdateOrderBooksInfo implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $serverTimestamp;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->serverTimestamp = date('U');
    }

    /**
     * @return void
     */
    public function handle()
    {
        $exchangeMarkets = Cache::remember(
            'exchange_markets',
            now()->addDay(),
            function () {
                return ExchangeMarket::orderBy('sort')->get();
            }
        );

        foreach ($exchangeMarkets as $exmInDB) {
            $exchangeMarket = ExchangeMarketFabric::get($exmInDB->code);
            if (!$exchangeMarket) {
                continue;
            }
            $exchangeMarket->idInDB = $exmInDB->id; // так экономим запросы, сразу присваивая id объектам
            $answer = $exchangeMarket->getOrderBook();

            if (!empty($answer)) {
                foreach ($answer as $currenciesCode => $pair) {
                    if (strpos($currenciesCode, '_') === false) {
                        continue;
                    }

                    list($currency1Code, $currency2Code) = explode(
                        '_',
                        $currenciesCode
                    ); // todo такая запись актуальна возможно только для эксмо

                    $currencyPairCode = $exmInDB->code.'.'.$currency1Code.'.'.$currency2Code;

                    if ($pair['ask_quantity']) { // todo ключи актуальны только для exmo
                        SellQuantity::store(
                            $currencyPairCode,
                            $pair['ask_quantity'],
                            $this->serverTimestamp
                        );
                    }
                    if ($pair['bid_amount']) { // todo ключи актуальны только для exmo
                        BuyAmount::store(
                            $currencyPairCode,
                            $pair['bid_amount'],
                            $this->serverTimestamp
                        );
                    }
                    if ($pair['ask_top'] && $pair['bid_top']) { // todo ключи актуальны только для exmo
                        Spread::store(
                            $currencyPairCode,
                            $pair['ask_top'] - $pair['bid_top'],
                            $this->serverTimestamp
                        );
                    }
                }
            }
        }
    }
}