<?php

namespace App\ExchangeMarkets;

use App\Models\ExchangeMarketCurrencyPair;
use App\Trading\Currency;
use App\Trading\CurrencyPairRate;
use App\Trading\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExmoExchangeMarket extends ExchangeMarket
{

    public function getCode(): string
    {
        return 'exmo';
    }

    public static $commissionPercent = 0.2;

    /**
     * @param string $public_key
     * @param string $secret_key
     *
     * @return bool
     */
    public function connect($public_key, $secret_key): bool
    {
        $output = $this->makeRequest('user_info', [], 'post', $public_key, $secret_key);

        $connected = !empty($output['balances']);

        return $connected;
    }

    public function getBalances($public_key, $secret_key)
    {
        $output = $this->makeRequest('user_info', [], 'post', $public_key, $secret_key);

        $answer = [];

        if (!empty($output['balances'])) {
            foreach ($output['balances'] as $currencyCode => $amount) {
                $answer[] = [
                    'currency' => new Currency($currencyCode),
                    'amount'   => $amount,
                ];
            }
        }

        return $answer;
    }

    public function getCurrenciesRates()
    {
        $output = $this->makeRequest('ticker');

        if (!is_array($output)) {
            return false;
        }

        $rates = [];
        foreach ($output as $currencyPairCode => $currencyRate) {
            if (strpos($currencyPairCode, '_') === false) { // пришли какие-то не те данные
//                Log::notice('Exmo get rates fail', [
//                    'currencyPairCode' => $currencyPairCode,
//                    'currencyRate'     => $currencyRate,
//                ]);

                continue;
            }

            list($currency1, $currency2) = explode('_', $currencyPairCode);

            // округляем время котировки до минуты, чтобы легче было искать метрики в кеше. Плюс берём время сервера, а не биржи, чтобы обходить баги и падения на биржах
            $rates[] = new CurrencyPairRate(
                $this->getCode().'.'.$currency1.'.'.$currency2, // todo довольно хрупкий момент
                $currencyRate['buy_price'],
                $currencyRate['sell_price'],
                $this->floorToMinute(date('U'))
            );

            // из Exmo вместе с котировками приходят ещё вспомогательные данные. Это high - самая высокая цена за сутки и low - самая низкая цена за сутки. Но решено их не собирать
        }

        return [
            'rates' => $rates,
        ];
    }

    public function placeOrder(Order $order, $public_key, $secret_key): string
    {
        $answer = $this->makeRequest(
            'order_create',
            [
                'pair'     => $order->currency1->code.'_'.$order->currency2->code,
                'quantity' => $order->amount,
                'price'    => $order->price,
                'type'     => $order->action,
            ],
            'post',
            $public_key,
            $secret_key
        );

        if ($answer['result']) {
            if ($answer['order_id']) {
                return $answer['order_id'];
            }
        }

        Log::notice(
            'Exmo order place error',
            [
                'currency1'  => $order->currency1->code,
                'currency2'  => $order->currency2->code,
                'amount'     => $order->amount,
                'price'      => $order->price,
                'action'     => $order->action,
                'answer'     => $answer,
                'public_key' => $public_key,
                'secret_key' => $secret_key,
            ]
        );

        return false;
    }

    public function getDoneOrderInfo($idAtExm, $currency1Amount, $public_key, $secret_key)
    {
        $answer = $this->makeRequest(
            'order_trades',
            [
                'order_id' => $idAtExm,
            ],
            'post',
            $public_key,
            $secret_key
        );

        // ответ возвращается в виде информации об ордере со вложенным массивом сделок
        // поэтому, чтобы узнать, что ордер полностью завершён, мы складываем суммы в сделках и сравниваем их с числом из основной информации по ордеру
        if (!isset($answer['trades'])) {
            return false;
        }

        $quantityFromTrades = 0;
        foreach ($answer['trades'] as $trade) {
            $quantityFromTrades += $trade['quantity']; // quantity это всегда валюта 1
        }

        // если суммы сходятся (с погрешностью), то ордер считается выполненным
        $difference = abs($quantityFromTrades - $currency1Amount);
        $allowableError = 0.0000001; // до десятимиллионной
        if ($difference > $allowableError) {
            return false;
        }

        // чтобы не делать лишних запросов к бирже, сразу же возвращаем и полученное количество
        return [
            'gained_amount' => $answer['in_amount'],
        ];
    }

    public function getOpenOrders($public_key, $secret_key)
    {
        return $this->makeRequest(
            'user_open_orders',
            [
            ],
            'post',
            $public_key,
            $secret_key
        );
    }

    public function getCancelledOrders($public_key, $secret_key, $offset = 0, $limit = 100)
    {
        return $this->makeRequest(
            'user_cancelled_orders',
            [
                'offset' => $offset,
                'limit'  => $limit,
            ],
            'post',
            $public_key,
            $secret_key
        );
    }

    public function getUserTrades(
        $currency_1_code,
        $currency_2_code,
        $public_key,
        $secret_key,
        $offset = 0,
        $limit = 100
    ) {
        return $this->makeRequest(
            'user_trades',
            [
                "pair"   => $currency_1_code."_".$currency_2_code,
                "limit"  => $limit,
                "offset" => $offset,
            ],
            'post',
            $public_key,
            $secret_key
        );
    }

    public function makeRequest($apiMethod, $params = [], $requestMethod = 'get', $public_key = '', $secret_key = '')
    {
        $ch = curl_init();
        $url = "https://api.exmo.me/v1/{$apiMethod}/";
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $mt = explode(' ', microtime());
        $params['nonce'] = $mt[1].substr(
                $mt[0],
                2,
                7
            ); // обязательный параметр для exmo (2 - чтобы после запятой, 7 - случайное число)
        $postData = http_build_query($params, '', '&');

        if ($requestMethod == 'post') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
        if ($requestMethod == 'get') {
            $url .= '?'.$postData;
        }
        curl_setopt($ch, CURLOPT_URL, $url);

        if ($public_key && $secret_key) {
            $sign = hash_hmac('sha512', $postData, $secret_key);
            $headers = Array(
                'Sign: '.$sign,
                'Key: '.$public_key,
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $output = $this->convertAnswerToArray(json_decode(curl_exec($ch)));
        curl_close($ch);

        return $output;
    }

    public function convertAnswerToArray($answer)
    {
        if (is_object($answer) || is_array($answer)) {
            $answer = (array)$answer;
            foreach ($answer as &$item) {
                $item = $this->convertAnswerToArray($item);
            }
        }

        return $answer;
    }

    /**
     * Получение списка валют и их лимитов
     *
     * @param integer $idInDB
     *
     * @return mixed
     */
    function updatePairsAndSettings($idInDB = null)
    {
        $pairs = $this->makeRequest('pair_settings');

        if (!is_array($pairs)) {
            return false;
        }

        foreach ($pairs as $pairsCodes => $pairArray) {
            list($currency1Code, $currency2Code) = explode('_', $pairsCodes);

            ExchangeMarketCurrencyPair::updateOrCreate(
                [
                    'currency_1_code'    => $currency1Code,
                    'currency_2_code'    => $currency2Code,
                    'exchange_market_id' => $idInDB,
                ],
                [
                    'currency_1_code'       => $currency1Code,
                    'currency_2_code'       => $currency2Code,
                    'exchange_market_id'    => $idInDB,
                    'currency_1_min_amount' => $pairArray['min_quantity'],
                    'currency_1_max_amount' => $pairArray['max_quantity'],
                    'min_price'             => $pairArray['min_price'],
                    'max_price'             => $pairArray['max_price'],
                    'currency_2_max_amount' => $pairArray['max_amount'],
                    'currency_2_min_amount' => $pairArray['min_amount'],
                    'commission_percents'   => static::$commissionPercent,
                ]
            );
        }
    }

    // todo возвращать не прямой запрос, а универсальный массив
    // todo передавать в ответе айдишники, чтобы не приходилось делать лишний запрос при обработке данных
    public function getOrderBook()
    {
        $currencyPairs = Cache::remember(
            'exmo_active_currency_pairs',
            now()->addDay(),
            function () {
                return ExchangeMarketCurrencyPair::where('exchange_market_id', $this->getIDinDB())->active()->get();
            }
        );

        $codesForRequest = [];
        foreach ($currencyPairs as $currencyPair) {
            $codesForRequest[] = $currencyPair->currency_1_code.'_'.$currencyPair->currency_2_code;
        }

        return $this->makeRequest('order_book', ['pair' => implode(',', $codesForRequest)]);
    }
}