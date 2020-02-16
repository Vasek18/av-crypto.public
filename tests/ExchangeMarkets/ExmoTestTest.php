<?php

namespace Tests\ExchangeMarkets;

use App\Jobs\CheckOrders;
use App\Jobs\UpdateCurrencyRates;
use App\Models\Basket;
use App\Models\ExchangeMarket;
use App\Models\ExchangeMarketCurrencyPair;
use App\Models\ExchangeMarketUserAccount;
use App\Models\Order;
use App\Trading\CurrencyPairRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ExmoTestTest extends TestCase
{

    use RefreshDatabase;

    public $userAccount;
    public $exchangeMarket;
    public $basket;

    public function setUp()
    {
        parent::setUp();

    }

    /**
     * @test
     *
     * @return void
     */
    public function exchangeMarketReturnsRatesAndItIsEqualToExmos()
    {
        // создаём нужные валютные пары. Раньше валюты exmo создавалась через миграцию, но это лишнее для тестирования в общем, поэтому создаём одну валюту специально для этого теста
        $exmoExchangeMarket = ExchangeMarket::where('code', 'exmo')->first();
        $exmoCurrencyPair = ExchangeMarketCurrencyPair::create(
            array(
                'currency_1_code'       => 'BTC',
                'currency_2_code'       => 'USD',
                'currency_1_min_amount' => 0.001,
                'currency_1_max_amount' => 100,
                'min_price'             => 1,
                'max_price'             => 30000,
                'currency_2_max_amount' => 200000,
                'currency_2_min_amount' => 1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoExchangeMarket->id,
                'active'                => true,
            )
        );
        $exmoTestExchangeMarket = ExchangeMarket::where('code', 'exmo_test')->first();
        $exmoTestCurrencyPair = ExchangeMarketCurrencyPair::firstOrCreate(
            array(
                'currency_1_code'    => 'BTC',
                'currency_2_code'    => 'USD',
                'exchange_market_id' => $exmoTestExchangeMarket->id,
                'active'             => true,
            ),
            array(
                'currency_1_code'       => 'BTC',
                'currency_2_code'       => 'USD',
                'currency_1_min_amount' => 0.001,
                'currency_1_max_amount' => 100,
                'min_price'             => 1,
                'max_price'             => 30000,
                'currency_2_max_amount' => 200000,
                'currency_2_min_amount' => 1,
                'commission_percents'   => 0.2,
                'exchange_market_id'    => $exmoTestExchangeMarket->id,
                'active'                => true,
            )
        );
        Cache::forget('active_currency_pairs'); // нужно забыть список активных пар после этого

        // получаем котировки
        UpdateCurrencyRates::dispatchNow();

        // получаем котировку Exmo
        $lastExmoRate = CurrencyPairRate::getLast($exmoCurrencyPair->code);
        // получаем котировку тестового Exmo
        $lastExmoTestRate = CurrencyPairRate::getLast($exmoTestCurrencyPair->code);

        $this->assertEquals($lastExmoRate->buy_price, $lastExmoTestRate->buy_price);
        $this->assertEquals($lastExmoRate->sell_price, $lastExmoTestRate->sell_price);
    }

    /**
     * @test
     *
     * @return void
     */
    public function ordersAreDoneInNextButOneTick()
    {
        // ставим ордер
        $userAccount = factory(ExchangeMarketUserAccount::class)->create();
        $basket = Basket::create(
            [
                'start_sum'              => 1,
                'account_id'             => $userAccount->id,
                'currency_pair_id'       => $this->getCurrencyPair('exmo_test')->id,
                'currency_1_last_amount' => null,
                'currency_2_last_amount' => null,
                'next_action'            => SELL_ACTION_CODE,
                'strategy'               => DEFAULT_TRADER,
            ]
        );
        $basket->createOrder(5);

        // запускаем проверку ордеров
        CheckOrders::dispatchNow();

        // проверяем, что у корзинки нуэное действие
        $basketFromDB = Basket::find($basket->id);
        $this->assertEquals(SELL_ACTION_CODE, $basketFromDB->next_action);

        // ждём минуту
        sleep(SECONDS_IN_MINUTE);

        // снова запускаем проверку ордеров
        CheckOrders::dispatchNow();

        // ордер должен быть отработан, проверяем через изменение следующего действия корзинки
        $basketFromDB = Basket::find($basket->id);
        $this->assertEquals(BUY_ACTION_CODE, $basketFromDB->next_action);

        // проверяем и сам ордер
        $order = Order::where('basket_id', $basket->id)->first();
        $this->assertEquals(true, $order->done);
    }
}
