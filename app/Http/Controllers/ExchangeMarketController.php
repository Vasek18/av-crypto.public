<?php

namespace App\Http\Controllers;

use App\ExchangeMarkets\ExchangeMarketFabric;
use App\Models\ExchangeMarket;
use App\Models\ExchangeMarketUserAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExchangeMarketController extends Controller{
    public function connect(Request $request, ExchangeMarket $exchangeMarket){
        $this->validate($request, [
            'api_key'    => 'required',
            'secret_key' => 'required',
        ]);

        $exmClass = ExchangeMarketFabric::get($exchangeMarket->code);

        if ($exmClass->connect($request->api_key, $request->secret_key)){
            $account = ExchangeMarketUserAccount::updateOrCreate(
                [
                    'exchange_market_id' => $exchangeMarket->id,
                    'user_id'            => Auth::id(),
                ],
                [
                    'exchange_market_id' => $exchangeMarket->id,
                    'user_id'            => Auth::id(),
                    'active'             => true,
                    'public_key'         => $request->api_key,
                    'secret_key'         => $request->secret_key,
                ]
            );

            if ($account){
                return [
                    'account' => [
                        'id' => $account->id
                    ]
                ];
            }
        }

        return [
            'errors' => [
                'Ошибка подключения'
            ]
        ];
    }

    public function getCurrencyPairs(Request $request, ExchangeMarket $exchangeMarket){
        return [
            'currency_pairs' => $exchangeMarket->currency_pairs()->get()
        ];
    }

}
