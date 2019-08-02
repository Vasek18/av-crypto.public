<?php

namespace App\Http\Controllers;

use App\Models\Basket;
use App\Models\ExchangeMarketCurrencyPair;
use App\Models\ExchangeMarketUserAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BasketController extends Controller
{

    public function store(Request $request)
    {
        $this->validate(
            $request,
            [
                'start_sum'          => 'required',
                'currency_1'         => 'required',
                'currency_2'         => 'required',
                'exchange_market_id' => 'required',
                'account_id'         => 'required',
            ]
        );

        // проверка на владение аккаунтом
        /** @var ExchangeMarketUserAccount $account */
        $account = ExchangeMarketUserAccount::where('id', $request->account_id)
            ->where('exchange_market_id', $request->exchange_market_id)
            ->where('active', true)
            ->first();
        if (!$account || $account->user_id != Auth::id()) {
            abort(404);
        }

        // получаем id пары
        $currencyPair = ExchangeMarketCurrencyPair
            ::where('currency_1_code', $request->currency_1)
            ->where('currency_2_code', $request->currency_2)
            ->where('exchange_market_id', $request->exchange_market_id)
            ->first();
        if (!$currencyPair) {
            return [
                'errors' => [
                    'Ошибка!',
                ],
            ];
        }

        // получение значения второй валюты для подстановки в предыдущее количество ещё до первого ордера, чтобы не начать играть не в тот момент
        $lastCurrency2ForCreation = Basket::getLastCurrency2ForCreation($request->start_sum, $currencyPair);

        // проверяется наличие хотя бы одной котировки, а следовательно поддержки валютной пары на бирже
        if (!$lastCurrency2ForCreation) { // если нет записей о котировках, то скорее всего это тестовая площадка, но всё равно нужно обрабатывать такие ситуации
            return [
                'errors' => [
                    'Эта пара не поддерживается',
                ],
            ];
        }

        // проверка баланса пользователя
        if (!$account->haveEnoughCurrencyAmount($request->currency_1, $request->start_sum)) {
            return [
                'errors' => [
                    'Недостаточно средств',
                ],
            ];
        }

        $basket = Basket::create(
            [
                'start_sum'              => $request->start_sum,
                'currency_pair_id'       => $currencyPair->id,
                'account_id'             => $request->account_id,
                'next_action'            => SELL_ACTION_CODE, // сначала продаём
                'currency_1_last_amount' => $request->start_sum,
                'currency_2_last_amount' => $lastCurrency2ForCreation,
                'strategy'               => $this->getStrategyNameForNewBasket(),
            ]
        );

        if ($basket) {
            return [
                'basket' => [
                    'id'                     => $basket->id,
                    'next_action'            => $basket->next_action,
                    'currency_1_last_amount' => $basket->currency_1_last_amount,
                    'start_sum'              => $basket->start_sum,
                    'currency_pair'          => [
                        'currency_1_code' => $currencyPair->currency_1_code,
                        'currency_2_code' => $currencyPair->currency_2_code,
                    ],
                ],
            ];
        }

        return [
            'errors' => [
                'Ошибка',
            ],
        ];
    }

    public function getStrategyNameForNewBasket()
    {
        return 'OtstupTrader';
    }

    public function destroy(Basket $basket, Request $request)
    {
        if ($basket->orders()->undone()->count()) {
            // если есть открытые ордера - выводим ошибку
            return [
                'success' => false,
                'message' => 'Нельзя удалить корзинку, у которой есть активные ордера',
            ];
        } else {
            if ($basket->orders()->done()->count()) { // если есть только закрытые ордера - архивируем
                $res = $basket->update(['archive' => true]);
                if ($res) {
                    return [
                        'success' => true,
                        'message' => 'Корзинка архивирована',
                    ];
                }
            }
        }

        if (!$basket->userOwnTheBasket($request->user()->id)) {
            return [
                'success' => false,
                'message' => 'Такой корзинки нет',
            ];
        }

        try {
            if ($basket->delete()) {
                return [
                    'success' => true,
                ];
            }
        } catch (\Exception $e) {
        }

        return [
            'success' => false,
            'message' => 'Произошла ошибка',
        ];
    }
}