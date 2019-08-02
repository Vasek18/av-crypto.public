<?php

namespace App\Jobs;

use App\CurrencyPairsMetrics\Average;
use App\CurrencyPairsMetrics\BuyAmount;
use App\CurrencyPairsMetrics\Extremum;
use App\CurrencyPairsMetrics\Macd;
use App\CurrencyPairsMetrics\MacdAverage;
use App\CurrencyPairsMetrics\SellQuantity;
use App\CurrencyPairsMetrics\Spread;
use App\CurrencyPairsMetrics\Trend;
use App\Models\ExchangeMarketCurrencyPair;
use App\Models\TraderDecision;
use App\Trading\CurrencyPairRate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ClearDB implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * todo наверное стоит очищать старые метрики самого сайта
     *
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $nowTimestamp = date('U');
        if (env('APP_ENV') !== 'testing') {
            $notLateThanTimestamp = $nowTimestamp - SECONDS_TO_KEEP_RATES_IN_DB;
        } else {
            $notLateThanTimestamp = $nowTimestamp - SECONDS_TO_KEEP_RATES_IN_DB_FOR_TEST; // уменьшаем время выполнения тестов // todo вроде бы есть более красивый способ переопределения констант
        }

        // удаляем старые решения трейдеров
        TraderDecision::where('timestamp', '<', $notLateThanTimestamp)->delete();

        foreach (ExchangeMarketCurrencyPair::active()->get() as $currencyPair) {
            // удаляем старые записи о котировках
            CurrencyPairRate::clearOlderThan($currencyPair->code, $notLateThanTimestamp);
            // удаляем старые метрики пар
            $this->clearOldCurrencyPairMetricValues($currencyPair->code, $notLateThanTimestamp);
        }
    }

    public function clearOldCurrencyPairMetricValues($currencyPairCode, $notLateThanTimestamp)
    {
        // тут лучше захардкоженно перечислить метрики, чем как-то хранить заполненные по дням
        Average::clearOlderThan($currencyPairCode, $notLateThanTimestamp);
        Extremum::clearOlderThan($currencyPairCode, $notLateThanTimestamp);
        Macd::clearOlderThan($currencyPairCode, $notLateThanTimestamp);
        MacdAverage::clearOlderThan($currencyPairCode, $notLateThanTimestamp);
        SellQuantity::clearOlderThan($currencyPairCode, $notLateThanTimestamp);
        BuyAmount::clearOlderThan($currencyPairCode, $notLateThanTimestamp);
        Spread::clearOlderThan($currencyPairCode, $notLateThanTimestamp);
        Trend::clearOlderThan($notLateThanTimestamp);
    }
}