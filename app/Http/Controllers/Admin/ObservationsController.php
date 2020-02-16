<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurrencyPairEventObservation;
use App\Models\ExchangeMarket;
use App\Models\ExchangeMarketCurrencyPair;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ObservationsController extends Controller
{
    public function index(Request $request)
    {
        $exchangeMarkets = Cache::remember(
            'exchange_markets',
            now()->addDay(),
            function () {
                return ExchangeMarket::orderBy('sort')->get();
            }
        );

        $currencyPairs = Cache::remember(
            'active_currency_pairs',
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

        $observations = [];
        if ($request->currency_pair) {
            $currencyPair = ExchangeMarketCurrencyPair::find($request->currency_pair);
            $observations = CurrencyPairEventObservation::where('currency_pair_code', $currencyPair->code)->get();
        }

        return view(
            'admin.observations.index',
            [
                'exchange_markets'         => $exchangeMarkets,
                'currency_pairs'           => $currencyPairs,
                'observations'             => $observations,
                'percent'                  => CurrencyPairEventObservation::getThresholdPercent(),
                'minimum_events_threshold' => 10,
            ]
        );
    }
}