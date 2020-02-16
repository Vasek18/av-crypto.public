<?php

namespace Tests\Jobs;

use App\Jobs\CheckOrders;
use App\Models\Basket;
use App\Models\ExchangeMarket;
use App\Models\ExchangeMarketUserAccount;
use App\Models\Metrics\Metrics;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckOrdersTest extends TestCase
{

    use RefreshDatabase;

    public $userAccount;
    public $exchangeMarket;
    public $basket;

    public function setUp()
    {
        parent::setUp();

        $this->userAccount = factory(ExchangeMarketUserAccount::class)->create();
        $this->exchangeMarket = ExchangeMarket::where('code', 'test')->first();

        $this->basket = Basket::create(
            [
                'start_sum'              => 1,
                'currency_pair_id'       => $this->testCurrencyPair->id,
                'account_id'             => $this->userAccount->id,
                'currency_1_last_amount' => null,
                'currency_2_last_amount' => null,
                'next_action'            => SELL_ACTION_CODE,
                'strategy'               => DEFAULT_TRADER,
            ]
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function ordersAreMarkedAsDoneAfterSuccessCheck()
    {
        $order = $this->basket->createOrder(1.5);

        // тут стоит учесть, что тестовая биржа всегда возвращает успех
        CheckOrders::dispatchNow();

        $orderFromDB = Order::find($order->id);

        $this->assertTrue(!!$orderFromDB->done);
    }

    /**
     * @test
     *
     * @return void
     */
    public function metricsUpdatesAfterOrderCheck()
    {
        $order = $this->basket->createOrder(1.5);

        // тут стоит учесть, что тестовая биржа всегда возвращает успех
        CheckOrders::dispatchNow();

        $metric = Metrics::where('code', 'time_order_being_open')->first();

        $this->assertEquals(1, $metric->values()->count());
    }

    /**
     * @test
     *
     * @return void
     */
    public function basketChangesAfterOrderIsDone()
    {
        $order = $this->basket->createOrder(5);
        // тут стоит учесть, что тестовая биржа всегда возвращает успех
        CheckOrders::dispatchNow();
        $basketFromDB = Basket::find($this->basket->id);

        $this->assertEquals(BUY_ACTION_CODE, $basketFromDB->next_action);
        $this->assertEquals(4.99, $basketFromDB->currency_2_last_amount);

        $order = $basketFromDB->createOrder(3);
        CheckOrders::dispatchNow();
        $basketFromDB = Basket::find($this->basket->id);

        $this->assertEquals(SELL_ACTION_CODE, $basketFromDB->next_action);
        $this->assertEquals(1.66000666, $basketFromDB->currency_1_last_amount);
        $this->assertEquals(4.99, $basketFromDB->currency_2_last_amount);
    }

    /**
     * @test
     *
     * @return void
     */
    public function basketDoesNotChangeIfOrderIsNotDone()
    {
        $order = $this->basket->createOrder(1);

        define('TEST_EXCHANGE_MARKET_FAIL', true);
        CheckOrders::dispatchNow();

        $basketFromDB = Basket::find($this->basket->id);

        $this->assertEquals(SELL_ACTION_CODE, $basketFromDB->next_action);
        $this->assertEquals(null, $basketFromDB->currency_2_last_amount);
    }
}