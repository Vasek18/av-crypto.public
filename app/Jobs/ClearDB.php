<?php

namespace App\Jobs;

use App\CurrencyPairsMetrics\Average;
use App\CurrencyPairsMetrics\BuyAmount;
use App\CurrencyPairsMetrics\Extremum;
use App\CurrencyPairsMetrics\Macd;
use App\CurrencyPairsMetrics\MacdAverage;
use App\CurrencyPairsMetrics\SellQuantity;
use App\CurrencyPairsMetrics\Spread;
use App\Models\ExchangeMarketCurrencyPair;
use App\Models\Metrics\MetricsValue;
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

    public $nowTimestamp;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->nowTimestamp = date('U');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $notLateThanTimestampForTrading = $this->nowTimestamp - SECONDS_TO_KEEP_RATES_IN_DB;

        // удаляем старые решения трейдеров
        $this->clearOldTradersDecisions($notLateThanTimestampForTrading);

        // удаляем старые значения метрик сайта
        $this->clearOldSiteMetricsValues();

        foreach (ExchangeMarketCurrencyPair::active()->get() as $currencyPair) {
            // удаляем старые записи о котировках
            CurrencyPairRate::clearOlderThan($currencyPair->code, $notLateThanTimestampForTrading);
            // удаляем старые метрики пар
            $this->clearOldCurrencyPairMetricValues($currencyPair->code, $notLateThanTimestampForTrading);
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
    }

    public function clearOldTradersDecisions($notLateThanTimestamp)
    {
        TraderDecision::where('timestamp', '<', $notLateThanTimestamp)->delete();
    }

    public function clearOldSiteMetricsValues()
    {
        $notLateThanTimestamp = $this->nowTimestamp - SECONDS_TO_KEEP_METRICS_IN_DB;
        MetricsValue::where('timestamp', '<', $notLateThanTimestamp)->delete();
    }
}