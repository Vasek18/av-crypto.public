<?php

namespace Tests\Feature;

use App\Jobs\UpdateCurrencyRates;
use App\Models\Basket;
use App\Models\ExchangeMarket;
use App\Models\ExchangeMarketUserAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class BasketsTest extends TestCase
{

    use RefreshDatabase;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;

    public $user;
    public $userAccount;
    public $exchangeMarket;

    public function setUp()
    {
        parent::setUp();

        $this->userAccount = factory(ExchangeMarketUserAccount::class)->create();
        $this->user = User::where('id', $this->userAccount->user_id)->first();
        $this->exchangeMarket = ExchangeMarket::where('code', 'test')->first();

        // чистим метрики валют
        Redis::flushall();

        // получаем котировки
        UpdateCurrencyRates::dispatchNow();
    }

    /**
     * @test
     *
     * @return void
     */
    public function roundMethodTest()
    {
        $this->assertEquals(0, Basket::round(0));

        $this->assertEquals(0.00000001, Basket::round(0.00000001));

        $this->assertEquals(1, Basket::round(1));
        $this->assertEquals(1, Basket::round(1.00000000));
        $this->assertEquals(1, Basket::round(1.000000001));

        $this->assertEquals(0.43578933, Basket::round(0.43578933));
        $this->assertEquals(0.43578933, Basket::round(0.43578933808));
        $this->assertEquals(0.4, Basket::round(0.4));
        $this->assertEquals(0.4, Basket::round(0.40));

        $this->assertEquals(12345670.4, Basket::round(12345670.400));

        $this->assertEquals(
            100000.12345678,
            Basket::round((double)100000.123456789)
        ); // если добавить ещё один знак, то php сам округляет при передачи в метод
    }

    /**
     * @test
     *
     * @return void
     */
    public function user_cannot_delete_not_his_basket()
    {
        $currencyPair = $this->getCurrencyPair();
        $basket = Basket::create(
            [
                'start_sum'              => 0.777,
                'currency_pair_id'       => $currencyPair->id,
                'account_id'             => $this->userAccount->id,
                'currency_1_last_amount' => null,
                'currency_2_last_amount' => null,
                'next_action'            => SELL_ACTION_CODE,
                'strategy'               => DEFAULT_TRADER,
            ]
        );

        // логинимся под другого человека
        $this->actingAs(factory(\App\Models\User::class)->create());

        $response = $this->json('DELETE', 'basket/'.$basket->id);

        $response->assertStatus(200)
            ->assertJson(
                [
                    'success' => false,
                    'message' => 'Такой корзинки нет',
                ]
            );
    }

    /**
     * @test
     *
     * @return void
     */
    public function user_cannot_create_basket_with_funds_he_doesnt_have()
    {
        // логинимся
        $this->actingAs($this->user);

        // смотрим, что при подходящих условиях корзинка создаётся
        $response = $this->json(
            'POST',
            'basket',
            [
                'start_sum'          => 2,
                'currency_1'         => 'BTC',
                'currency_2'         => 'USD',
                'exchange_market_id' => $this->exchangeMarket->id,
                'account_id'         => $this->userAccount->id,
            ]
        );
        $response->assertStatus(200);
        $responseArray = $response->decodeResponseJson();
        $this->assertArrayHasKey('basket', $responseArray);
        $this->assertArrayNotHasKey('errors', $responseArray);

        // но если выйти за лимит (2,5 BTC у тестового, а 2 + 2 = 4), то корзинка не создаётся
        $response = $this->json(
            'POST',
            'basket',
            [
                'start_sum'          => 2,
                'currency_1'         => 'BTC',
                'currency_2'         => 'USD',
                'exchange_market_id' => $this->exchangeMarket->id,
                'account_id'         => $this->userAccount->id,
            ]
        );
        $response->assertStatus(200);
        $responseArray = $response->decodeResponseJson();
        $this->assertArrayNotHasKey('basket', $responseArray);
        $this->assertArrayHasKey('errors', $responseArray);
    }

    /**
     * @test
     *
     * @return void
     */
    public function test_occupied_in_baskets_calculation()
    {
        $currencyPair1 = $this->getCurrencyPair('test', 'BTC', 'USD');
        $basket1 = $this->userAccount->baskets()->create(
            [
                'start_sum'              => 1,
                'currency_pair_id'       => $currencyPair1->id,
                'currency_1_last_amount' => 1,
                'currency_2_last_amount' => 1,
                'next_action'            => SELL_ACTION_CODE,
                'strategy'               => 'OtstupTrader',
            ]
        );
        $currencyPair2 = $this->getCurrencyPair('test', 'BTC', 'USD');
        $basket2 = $this->userAccount->baskets()->create(
            [
                'start_sum'              => 2,
                'currency_pair_id'       => $currencyPair2->id,
                'currency_1_last_amount' => 2,
                'currency_2_last_amount' => 2,
                'next_action'            => BUY_ACTION_CODE,
                'strategy'               => 'OtstupTrader',
            ]
        );
        $currencyPair3 = $this->getCurrencyPair('test', 'ETH', 'USD');
        $basket3 = $this->userAccount->baskets()->create(
            [
                'start_sum'              => 3,
                'currency_pair_id'       => $currencyPair3->id,
                'currency_1_last_amount' => 3,
                'currency_2_last_amount' => 3,
                'next_action'            => BUY_ACTION_CODE,
                'strategy'               => 'OtstupTrader',
            ]
        );

        $this->assertEquals(1, $this->userAccount->getAmountInBaskets('BTC'));
        $this->assertEquals(5, $this->userAccount->getAmountInBaskets('USD'));
        $this->assertEquals(0, $this->userAccount->getAmountInBaskets('RUB'));
    }
}