<?php

namespace App\Http\Controllers;

use App\Models\ExchangeMarket;
use App\Models\ExchangeMarketCurrencyPair;
use App\Trading\CurrencyPairRate;
use App\Traits\GetInfoForAnalysisGraph;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IndexController extends Controller
{
    use GetInfoForAnalysisGraph;

    public function index()
    {
        $exchangeMarkets = Cache::remember(
            'exchange_markets',
            now()->addDay(),
            function () {
                return ExchangeMarket::orderBy('sort')->get();
            }
        );
        $currencyPairs = Cache::remember(
            'currency_pairs_for_index_page',
            now()->addDay(),
            function () {
                return ExchangeMarketCurrencyPair
                    ::active()
                    ->orderBy('exchange_market_id')
                    ->orderBy('currency_1_code')
                    ->orderBy('currency_2_code')
                    ->get();
            }
        );

        return view(
            'welcome',
            [
                'exchange_markets' => $exchangeMarkets, // todo а зачем нам этот массив?
                'currency_pairs'   => $currencyPairs,
            ]
        );
    }

    // получение котировок, ордеров, маркеров
    public function getPairInfo(Request $request)
    {
        $timestampFrom = strtotime($request->dateFrom);
        $timestampTo = strtotime($request->dateTo);
        $periodInMinutes = ($timestampTo - $timestampFrom) / SECONDS_IN_MINUTE;

        $currencyPairCode = ExchangeMarketCurrencyPair::getCodeByID($request->currency_pair_id);

        $rates = CurrencyPairRate::getForPeriod(
            $currencyPairCode,
            $periodInMinutes,
            $timestampTo
        );

        $metrics = $this->getMetricValues(
            $currencyPairCode,
            $periodInMinutes,
            $timestampTo
        );

        return [
            'rates'   => $rates,
            'metrics' => $metrics,
        ];
    }

    public function getMetricValues($currencyPairCode, $minutes, $timestampTo)
    {
        $metrics = [];

        $metrics = array_merge(
            $metrics,
            $this->getExtremumsForAnalysisGraph($currencyPairCode, $minutes, $timestampTo)
        );
        $metrics = array_merge(
            $metrics,
            $this->getAveragesForAnalysisGraph($currencyPairCode, $minutes, $timestampTo)
        );
        $metrics = array_merge(
            $metrics,
            $this->getMacdsForAnalysisGraph($currencyPairCode, $minutes, $timestampTo)
        );
        $metrics = array_merge(
            $metrics,
            $this->getMacdAveragesForAnalysisGraph($currencyPairCode, $minutes, $timestampTo)
        );
        $metrics = array_merge(
            $metrics,
            $this->getOrderBookMetricsForAnalysisGraph($currencyPairCode, $minutes, $timestampTo)
        );

        return $metrics;
    }
}