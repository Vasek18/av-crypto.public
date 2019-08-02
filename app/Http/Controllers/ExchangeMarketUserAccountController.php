<?php

namespace App\Http\Controllers;

use App\Models\ExchangeMarketUserAccount;
use Illuminate\Support\Facades\Auth;

class ExchangeMarketUserAccountController extends Controller
{

    public function show(ExchangeMarketUserAccount $user_account)
    {
        if ($user_account->user_id != Auth::id()) {
            abort(404);
        }

        return [
            'balances' => $user_account->getBalances(),
            'exchange' => $user_account->exchange()->first(
                [
                    'id',
                    'name',
                ]
            ),
            'baskets'  => $user_account->baskets()->active()
                ->with(
                    [
                        'orders'       => function ($query) {
                            $query->orderBy('created_at', 'desc')
                                ->select(
                                    [
                                        'id',
                                        'basket_id',
                                        'amount',
                                        'gained_amount',
                                        'price',
                                        'action',
                                        'done',
                                        'created_at',
                                    ]
                                );
                        },
                        'currencyPair' => function ($query) {
                            $query->select(
                                [
                                    'id',
                                    'currency_1_code',
                                    'currency_2_code',
                                ]
                            );
                        },
                    ]
                )->get(
                    [
                        'id',
                        'currency_pair_id',
                        'currency_1_last_amount',
                        'currency_2_last_amount',
                        'start_sum',
                        'next_action',
                        'created_at',
                    ]
                ),
        ];
    }
}
