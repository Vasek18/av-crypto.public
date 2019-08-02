<?php

namespace Tests\Feature;

use App\Models\Basket;
use App\Models\ExchangeMarketUserAccount;
use App\Models\Metrics\Metrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCreatingTest extends TestCase
{

    use RefreshDatabase;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;

    public $userAccount;
    public $basket;

    public $precision = 8;

    public function setUp()
    {
        parent::setUp();

        $this->userAccount = factory(ExchangeMarketUserAccount::class)->create();
    }

    /**
     * @test
     *
     * @return void
     */
    public function itCanCreateSellOrder()
    {
        $basket = Basket::create(
            [
                'start_sum'              => 0.777,
                'account_id'             => $this->userAccount->id,
                'currency_pair_id'       => $this->getCurrencyPair()->id,
                'currency_1_last_amount' => null,
                'currency_2_last_amount' => null,
                'next_action'            => SELL_ACTION_CODE,
                'strategy'               => DEFAULT_TRADER,
            ]
        );

        /** @var Basket $basket */
        $order = $basket->createOrder('1.777');

        $this->assertInstanceOf('\App\Models\Order', $order);
        $this->assertEquals(
            0.777,
            $order->amount
        ); // при продаже мы имеющееся продаём количество, а не высчитываем ожидаемое

        $this->assertEquals(1, Metrics::where('code', 'successfully_created_orders_count')->first()->values()->count());
    }

    /**
     * @test
     *
     * @return void
     */
    public function itCanCreateBuyOrder()
    {
        $basket = Basket::create(
            [
                'start_sum'              => 1,
                'account_id'             => $this->userAccount->id,
                'currency_pair_id'       => $this->getCurrencyPair()->id,
                'currency_1_last_amount' => null,
                'currency_2_last_amount' => 1,
                'next_action'            => BUY_ACTION_CODE,
                'strategy'               => DEFAULT_TRADER,
            ]
        );

        /** @var Basket $basket */
        $order = $basket->createOrder(0.777);

        $this->assertInstanceOf('\App\Models\Order', $order);
        $this->assertEquals(1.28442728, $order->amount);

        $this->assertEquals(1, Metrics::where('code', 'successfully_created_orders_count')->first()->values()->count());
    }

    /**
     * @test
     *
     * @return void
     */
    public function itLogsIfExmReturnsError()
    {
        $basket = Basket::create(
            [
                'start_sum'              => 0.777,
                'account_id'             => $this->userAccount->id,
                'currency_pair_id'       => $this->getCurrencyPair()->id,
                'currency_1_last_amount' => null,
                'currency_2_last_amount' => null,
                'next_action'            => SELL_ACTION_CODE,
                'strategy'               => DEFAULT_TRADER,
            ]
        );

        define('TEST_EXCHANGE_MARKET_FAIL', true);

        /** @var Basket $basket */
        $order = $basket->createOrder('1.777');

        $this->assertEquals(
            1,
            Metrics::where('code', 'unsuccessfully_created_orders_count')->first()->values()->count()
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function itCannotCreateSellOrderWithAmountLessThanItWas()
    {
        $basket = Basket::create(
            [
                'start_sum'              => 1,
                'account_id'             => $this->userAccount->id,
                'currency_pair_id'       => $this->getCurrencyPair()->id,
                'currency_1_last_amount' => null,
                'currency_2_last_amount' => null,
                'next_action'            => SELL_ACTION_CODE,
                'strategy'               => DEFAULT_TRADER,
            ]
        );

        /** @var Basket $basket */
        $order1 = $basket->createOrder(5);
        $basket->commitOrder($order1);

        $order2 = $basket->createOrder(3);
        $basket->commitOrder($order2);

        $order3 = $basket->createOrder(1);

        $this->assertInstanceOf('\App\Models\Order', $order1);
        $this->assertInstanceOf('\App\Models\Order', $order2);
        $this->assertFalse($order3);
        $this->assertEquals(SELL_ACTION_CODE, $basket->next_action);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itCannotCreateBuyOrderWithAmountLessThanItWas()
    {
        /** @var Basket $basket */
        $basket = Basket::create(
            [
                'start_sum'              => 1,
                'account_id'             => $this->userAccount->id,
                'currency_pair_id'       => $this->getCurrencyPair()->id,
                'currency_1_last_amount' => null,
                'currency_2_last_amount' => null,
                'next_action'            => SELL_ACTION_CODE,
                'strategy'               => DEFAULT_TRADER,
            ]
        );

        $order1 = $basket->createOrder(2);
        $basket->commitOrder($order1);

        $order2 = $basket->createOrder(1);
        $basket->commitOrder($order2);

        $order3 = $basket->createOrder(3);
        $basket->commitOrder($order3);

        $order4 = $basket->createOrder(5);

        $this->assertInstanceOf('\App\Models\Order', $order1);
        $this->assertInstanceOf('\App\Models\Order', $order2);
        $this->assertInstanceOf('\App\Models\Order', $order3);
        $this->assertFalse($order4);
        $this->assertEquals(BUY_ACTION_CODE, $basket->next_action);
    }

    /**
     * @test
     *
     * @return void
     */
    public function basketConsidersLimits()
    {
        /** @var Basket $basket */
        $basket1 = Basket::create(
            [
                'start_sum'              => 1,
                'account_id'             => $this->userAccount->id,
                'currency_pair_id'       => $this->getCurrencyPair()->id,
                'currency_1_last_amount' => null,
                'currency_2_last_amount' => null,
                'next_action'            => SELL_ACTION_CODE,
                'strategy'               => DEFAULT_TRADER,
            ]
        );
        $order1 = $basket1->createOrder(1);
        $this->assertFalse($order1);

        /** @var Basket $basket */
        $basket2 = Basket::create(
            [
                'start_sum'              => 1,
                'account_id'             => $this->userAccount->id,
                'currency_pair_id'       => $this->getCurrencyPair()->id,
                'currency_1_last_amount' => null,
                'currency_2_last_amount' => 0.5,
                'next_action'            => BUY_ACTION_CODE,
                'strategy'               => DEFAULT_TRADER,
            ]
        );
        $order2 = $basket2->createOrder(1);
        $this->assertNotFalse($order2);

        /** @var Basket $basket */
        $basket3 = Basket::create(
            [
                'start_sum'              => 1,
                'account_id'             => $this->userAccount->id,
                'currency_pair_id'       => $this->getCurrencyPair()->id,
                'currency_1_last_amount' => null,
                'currency_2_last_amount' => 0.00001,
                'next_action'            => BUY_ACTION_CODE,
                'strategy'               => DEFAULT_TRADER,
            ]
        );
        $order3 = $basket3->createOrder(1);
        $this->assertFalse($order3);

        $this->assertContains('Order limits error', file_get_contents(storage_path().'/logs/laravel.log'));
    }
}
