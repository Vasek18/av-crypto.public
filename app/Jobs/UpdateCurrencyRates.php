<?php

namespace App\Jobs;

use App\Events\CurrencyPairRateChanged;
use App\ExchangeMarkets\ExchangeMarketFabric;
use App\Models\ExchangeMarket;
use App\Models\ExchangeMarketCurrencyPair;
use App\Models\Metrics\Metrics;
use App\Trading\CurrencyPairRate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

// эта задача так и должна называться, остальные вещи происходят в слушателях
class UpdateCurrencyRates implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $currencies;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->getCurrencies();
    }

    /**
     * @return void
     */
    public function handle()
    {
        // сбор котировок идёт по порядку сортировки. Одна из причин - ExmoTest
        $exchangeMarket = Cache::remember(
            'exchange_markets',
            now()->addDay(),
            function () {
                return ExchangeMarket::orderBy('sort')->get();
            }
        );

        foreach ($exchangeMarket as $exmInDB) {
            $exchangeMarket = ExchangeMarketFabric::get($exmInDB->code);
            if (!$exchangeMarket) {
                continue;
            }

            $answer = $exchangeMarket->getCurrenciesRates();

            if (!empty($answer['rates'])) {
                /** @var CurrencyPairRate $rate */
                foreach ($answer['rates'] as $rate) {
                    $currencyPairID = $this->getCurrencyPairID($rate->currencyPairCode);

                    // пропускаем валюты, которых нет или для которых не собираем данные
                    if (!$currencyPairID) {
                        continue;
                    }

                    // сохраняем котировку
                    CurrencyPairRate::save(
                        $rate->currencyPairCode,
                        $rate->buy_price,
                        $rate->sell_price,
                        $rate->timestamp
                    );

                    // запуск слушателей обновления котировок
                    event(
                        new CurrencyPairRateChanged(
                            $currencyPairID,
                            $rate->currencyPairCode,
                            $rate
                        )
                    );
                }
            }
        }

        // замер производительности
        Metrics::log(
            'check_rates_tick_execution_time',
            (date('U') - $rate->timestamp)
        );
    }

    public function getCurrencyPairID($currencyPairCode)
    {
        if (!empty($this->currencies[$currencyPairCode])) {
            return $this->currencies[$currencyPairCode];
        }

        return false;
    }

    public function getCurrencies()
    {
        $currencyPairs = Cache::remember(
            'active_currency_pairs',
            now()->addDay(),
            function () {
                return ExchangeMarketCurrencyPair::active()->get();
            }
        );
        foreach ($currencyPairs as $pair) {
            $this->currencies[$pair->code] = $pair->id;
        }
    }
}
