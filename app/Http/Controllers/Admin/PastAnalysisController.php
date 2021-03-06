<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurrencyPairEventObservation;
use App\Models\ExchangeMarket;
use App\Models\ExchangeMarketCurrencyPair;
use App\Models\Order;
use App\Models\TraderDecision;
use App\Models\TraderDecision as DesicionInDB;
use App\Trading\CurrencyPairRate;
use App\Trading\Event;
use App\Traits\GetInfoForAnalysisGraph;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class PastAnalysisController extends Controller
{
    use GetInfoForAnalysisGraph;

    public function index()
    {
        DesicionInDB::create(
            [
                'currency_pair_id' => 1,
                'trader_code'      => 'test'.rand(1, 3),
                'decision'         => 'S',
                'timestamp'        => date('U'),
            ]
        );

        $exchangeMarkets = Cache::remember(
            'exchange_markets',
            now()->addDay(),
            function () {
                return ExchangeMarket::orderBy('sort')->get();
            }
        );
        $currencyPairs = Cache::remember(
            'currency_pairs_for_past_analysis_page',
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
            'admin.past_analysis.index',
            [
                'exchange_markets' => $exchangeMarkets,
                // todo а зачем нам этот массив? // чтобы показывать название биржи у валют, но мб можно решить через получение кода валюты
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

        $orders = Order::where('currency_1_code', $request->currency_1_code)
            ->where('currency_2_code', $request->currency_2_code)
            ->where('exchange_market_id', $request->exchange_market_id)
            ->where('created_at', '>=', Carbon::createFromTimestamp($timestampFrom)->toDateTimeString())
            ->where('created_at', '<=', Carbon::createFromTimestamp($timestampTo)->toDateTimeString())
            ->get();

        $metrics = $this->getMetricValues(
            $currencyPairCode,
            $periodInMinutes,
            $timestampTo
        );

        $decisions = TraderDecision::where('currency_pair_id', $request->currency_pair_id)
            ->where('timestamp', '>=', $timestampFrom)
            ->where('timestamp', '<=', $timestampTo)
            ->get();

        $events = [];
        // если нужны последние сутки. Потому что события хранятся только сутки
        if ($timestampTo > date('U') - CurrencyPairEventObservation::getPeriodInSeconds()) {
            $events = Event::get($currencyPairCode);
        }

        return [
            'rates'     => $rates,
            'orders'    => $orders,
            'metrics'   => $metrics,
            'decisions' => $decisions,
            'events'    => $events,
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