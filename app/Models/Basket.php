<?php

namespace App\Models;

use App\ExchangeMarkets\ExchangeMarketFabric;
use App\Models\Metrics\Metrics;
use App\Trading\Currency;
use App\Trading\CurrencyPairRate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Basket extends Model
{
    protected $table    = 'baskets';
    protected $fillable = [
        'start_sum',
        'account_id',
        'currency_pair_id',
        'currency_1_last_amount',
        'currency_2_last_amount',
        'next_action',
        'archive',
        'strategy',
    ];

    public static $precision = 8;

    /**
     * todo может быть принимать ещё и действие?
     *
     * @param $price
     *
     * @return bool|Order
     */
    public function createOrder($price)
    {
        if ($this->orders()->undone()->count(
        )) { // todo тут на самом деле нужно проверять не то, что ордера вообще есть, а то что в корзинке ещё есть деньги за вычетом ордеров
            return false;
        }

        $amountAfterOrder = $this->calculateExpectedAmountAfterOrder($price);
        $amountForOrder = $this->calculateAmountForOrder($price);

        if (!$this->checkThatDecisionIsPositive($amountAfterOrder)) {
            return false;
        }

        if (!$this->checkThatAmountFitsInLimits($amountForOrder, $amountAfterOrder)) {
            return false;
        }

        $orderIdAtExm = $this->placeOrderAtExm($amountForOrder, $price);

        $dbOrderFields = [
            'currency_1_code'    => $this->currencyPair->currency_1_code,
            'currency_2_code'    => $this->currencyPair->currency_2_code,
            'basket_id'          => $this->id,
            'exchange_market_id' => $this->currencyPair->exchange_market_id,
            'amount'             => $amountForOrder,
            'gained_amount'      => $amountAfterOrder,
            // todo вообще мы должны получать это от биржи, но на эксмо не работает проверка ордеров
            'price'              => static::round($price),
            'action'             => $this->next_action,
            'id_at_exm'          => $orderIdAtExm,
        ];

        if ($orderIdAtExm) {
            $order = $this->orders()->create($dbOrderFields);

            if ($order) {
                Metrics::log('successfully_created_orders_count', 1);

                return $order;
            }

            // todo откатывать ордер на бирже в этом месте

            Log::notice(
                'Order db creating error',
                [
                    'order_array' => $dbOrderFields,
                    'order'       => $order,
                ]
            );
        }

        Log::notice(
            'Order exm creating error',
            [
                'public_key'         => $this->account->public_key,
                'secret_key'         => $this->account->secret_key,
                'currency_1_code'    => $dbOrderFields['currency_1_code'],
                'currency_2_code'    => $dbOrderFields['currency_2_code'],
                'basket_id'          => $dbOrderFields['basket_id'],
                'exchange_market_id' => $dbOrderFields['exchange_market_id'],
                'amount'             => $dbOrderFields['amount'],
                'gained_amount'      => $dbOrderFields['gained_amount'],
                'action'             => $dbOrderFields['action'],
            ]
        );

        Metrics::log('unsuccessfully_created_orders_count', 1);

        return false;
    }

    public function placeOrderAtExm($amount, $price)
    {
        $exmClass = ExchangeMarketFabric::get($this->currencyPair->exchange_market->code);
        $exmOrder = new \App\Trading\Order(
            new Currency($this->currencyPair->currency_1_code),
            new Currency($this->currencyPair->currency_2_code),
            $amount,
            $price,
            $this->next_action
        );

        // todo возможно потенциальная брежь в безопасности // можно хранить сами ключи в бд зашифрованными с солью
        return $exmClass->placeOrder($exmOrder, $this->account->public_key, $this->account->secret_key);
    }

    private function calculateExpectedAmountAfterOrder($price)
    {
        $currentAmount = ($this->next_action == BUY_ACTION_CODE ? $this->currency_2_last_amount : ($this->currency_1_last_amount ?: $this->start_sum));
        $amount = $this->next_action == BUY_ACTION_CODE ? $currentAmount / $price : $currentAmount * $price;

        $amount = $this->applyCommissionToNumber($amount, $this->currencyPair->commission_percents);

        return static::round($amount);
    }

    private function calculateAmountForOrder($price)
    {
        if ($this->next_action == BUY_ACTION_CODE) {
            $amount = $this->currency_2_last_amount / $price;
            $amount = $this->applyCommissionToNumber(
                $amount,
                $this->currencyPair->commission_percents
            ); // при покупке нужно применить комиссию
        }

        if ($this->next_action == SELL_ACTION_CODE) {
            $amount = ($this->currency_1_last_amount ?: $this->start_sum);
        }

        if (!empty($amount)) {
            return static::round($amount);
        }

        return false;
    }

    private function applyCommissionToNumber($number, $commisionPercent)
    {
        $commissionMultiplier = (100 - $commisionPercent) / 100;

        return $number * $commissionMultiplier;
    }

    private function checkThatDecisionIsPositive($amount)
    {
        // количество при продаже должно увеличиваться с учётом комиссии последней продажи
        if ($this->next_action == SELL_ACTION_CODE && $this->currency_2_last_amount) {
            if (static::round($this->currency_2_last_amount) > $amount) {

                return false;
            }
        }
        // так же и при покупке
        if ($this->next_action == BUY_ACTION_CODE && $this->currency_1_last_amount) {
            if (static::round($this->currency_1_last_amount) > $amount) {

                return false;
            }
        }

        return true;
    }

    private function checkThatAmountFitsInLimits($amountForOrder, $amountAfterOrder)
    {
        if (
            (
                $this->next_action == BUY_ACTION_CODE &&
                (
                    (
                        $this->currencyPair->currency_1_min_amount > $amountForOrder ||
                        $amountForOrder > $this->currencyPair->currency_1_max_amount
                    )
                    ||
                    (
                        $this->currencyPair->currency_1_min_amount > $amountAfterOrder ||
                        $amountAfterOrder > $this->currencyPair->currency_1_max_amount
                    )
                )
            )
            ||
            (
                $this->next_action == SELL_ACTION_CODE &&
                (
                    (
                        $this->currencyPair->currency_1_min_amount > $amountForOrder ||
                        $amountForOrder > $this->currencyPair->currency_1_max_amount
                    )
                    ||
                    (
                        $this->currencyPair->currency_2_min_amount > $amountAfterOrder ||
                        $amountAfterOrder > $this->currencyPair->currency_2_max_amount
                    )
                )
            )
        ) {
            Log::notice(
                'Order limits error',
                [
                    'amountForOrder'        => $amountForOrder,
                    'amountAfterOrder'      => $amountAfterOrder,
                    'currency_1_min_amount' => $this->currencyPair->currency_1_min_amount,
                    'currency_1_max_amount' => $this->currencyPair->currency_1_max_amount,
                    'currency_2_min_amount' => $this->currencyPair->currency_2_min_amount,
                    'currency_2_max_amount' => $this->currencyPair->currency_2_max_amount,
                    'action'                => $this->next_action,
                    'currency_1_code'       => $this->currencyPair->currency_1_code,
                    'currency_2_code'       => $this->currencyPair->currency_2_code,
                ]
            );
            Metrics::log('unsuccessfully_created_orders_count', 1);

            return false;
        }

        return true;
    }

    public function commitOrder(Order $order, $noStatistic = false)
    {
        if (!$noStatistic) {
            Metrics::log('time_order_being_open', (date('U') - $order->created_at->timestamp));
        }

        if ($order->action == BUY_ACTION_CODE) {
            if (!$noStatistic) {
//                Log::info('Basket change', [
//                    'basket_id'                   => $this->id,
//                    'action_from'                 => $order->action,
//                    'action_to'                   => SELL_ACTION_CODE,
//                    'currency_1_last_amount_from' => static::round($this->currency_1_last_amount),
//                    'currency_1_last_amount_to'   => static::round($order->gained_amount),
//                ]);
            }

            $this->update(
                [
                    'currency_1_last_amount' => static::round($order->gained_amount),
                    'next_action'            => SELL_ACTION_CODE,
                ]
            );
        } else {
            if (!$noStatistic) {
//                Log::info('Basket change', [
//                    'basket_id'                   => $this->id,
//                    'action_from'                 => $order->action,
//                    'action_to'                   => BUY_ACTION_CODE,
//                    'currency_2_last_amount_from' => static::round($this->currency_2_last_amount),
//                    'currency_2_last_amount_to'   => static::round($order->gained_amount),
//                ]);
            }

            $this->update(
                [
                    'currency_2_last_amount' => static::round($order->gained_amount),
                    'next_action'            => BUY_ACTION_CODE,
                ]
            );
        }

        $order->update(['done' => true]);
    }

    public static function round($sum)
    {
        return floor($sum * pow(10, static::$precision)) / pow(10, static::$precision);
    }

    public static function getLastCurrency2ForCreation($start_sum, $currencyPair)
    {
        $currencyPairLastRate = CurrencyPairRate::getLast($currencyPair->code);

        // нет никаких котировок по этой паре. Скорее всего только что запустили миграции с нуля. Просто возвращаем false
        if (!$currencyPairLastRate) {
            return false;
        }

        $commissionMultiplier = (100 + $currencyPair->commission_percents) / 100;

        return Basket::round(
            $start_sum * $currencyPairLastRate->buy_price * $commissionMultiplier
        ); // todo почему именно buy?
    }

    public function userOwnTheBasket($userID)
    {
        return $this->account->user_id == $userID;
    }

    // области видимости
    public function scopeActive($query)
    {
        return $query->where('archive', false);
    }

    // связи с другими моделями
    public function account()
    {
        return $this->belongsTo('App\Models\ExchangeMarketUserAccount', 'account_id');
    }

    public function exchangeMarket()
    {
        return $this->belongsTo('App\Models\ExchangeMarket', 'exchange_market_id');
    }

    public function orders()
    {
        return $this->hasMany('App\Models\Order', 'basket_id');
    }

    public function currencyPair()
    {
        return $this->belongsTo('App\Models\ExchangeMarketCurrencyPair', 'currency_pair_id');
    }
}