<?php

namespace Tests\Jobs;

use App\Jobs\UpdateCurrencyRates;
use App\Listeners\Trade;
use App\Models\Basket;
use App\Models\ExchangeMarket;
use App\Models\ExchangeMarketCurrencyPair;
use App\Models\ExchangeMarketUserAccount;
use App\Models\Metrics\Metrics;
use App\Models\Order;
use App\Models\TraderDecision;
use App\Trading\CurrencyPairRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class UpdateCurrencyRatesTest extends TestCase
{
    use RefreshDatabase;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;

    public $userAccount;

    public function setUp()
    {
        parent::setUp();

        $this->userAccount = factory(ExchangeMarketUserAccount::class)->create();

        // нужна хотя бы одна эксмовская валютная пара
        $exmoExchangeMarket = ExchangeMarket::where('code', 'exmo')->first();
        ExchangeMarketCurrencyPair::create(
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
        Cache::forget('active_currency_pairs'); // нужно забыть список активных пар после этого
    }

    /**
     * @test
     *
     * @return void
     */
    public function itSavesRatesOnlyOfActivePairs()
    {
        // запускаем сбор котировок
        UpdateCurrencyRates::dispatchNow();

        // Проходим по всем парам - котировки должны быть только у активных пар
        foreach (ExchangeMarketCurrencyPair::active()->get() as $currencyPair) {
            $this->assertEquals(1, CurrencyPairRate::count($currencyPair->code));
        }
        foreach (ExchangeMarketCurrencyPair::inActive()->get() as $currencyPair) {
            $this->assertEquals(0, CurrencyPairRate::count($currencyPair->code));
        }
    }

    /**
     * Проверяем, что котировка всегда округлена до минуты
     *
     * @test
     *
     * @return void
     */
    public function ratesAreAlwaysFlooredToMinute()
    {
        UpdateCurrencyRates::dispatchNow();

        foreach (ExchangeMarketCurrencyPair::active()->get() as $currencyPair) {
            $rate = CurrencyPairRate::getLast($currencyPair->code);
            $this->assertEquals(
                0,
                $rate->timestamp % SECONDS_IN_MINUTE
            );
        }
    }

    /**
     * @test
     *
     * @return void
     */
    public function itWritesMetrics()
    {
        $this->assertEquals(
            0,
            Metrics::where('code', 'time_from_rates_check_to_order_creation')->first()->values()->count()
        );
        $this->assertEquals(
            0,
            Metrics::where('code', 'check_rates_tick_execution_time')->first()->values()->count()
        );

        Basket::create(
            [
                'start_sum'              => 1000,
                'account_id'             => $this->userAccount->id,
                'currency_pair_id'       => $this->testCurrencyPair->id,
                'currency_1_last_amount' => null,
                'currency_2_last_amount' => 1000,
                'next_action'            => BUY_ACTION_CODE,
                'strategy'               => 'AlwaysBuyTrader',
            ]
        );

        UpdateCurrencyRates::dispatchNow();

        $this->assertGreaterThan(
            0,
            Metrics::where('code', 'time_from_rates_check_to_order_creation')->first()->values()->count()
        );
        $this->assertGreaterThan(
            0,
            Metrics::where('code', 'check_rates_tick_execution_time')->first()->values()->count()
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function archivedBasketsDontGetChosen()
    {
        Basket::create(
            [
                'start_sum'              => 0.001,
                'account_id'             => $this->userAccount->id,
                'currency_pair_id'       => $this->testCurrencyPair->id,
                'currency_1_last_amount' => null,
                'currency_2_last_amount' => null,
                'next_action'            => SELL_ACTION_CODE,
                'archive'                => false,
                'strategy'               => DEFAULT_TRADER,
            ]
        );
        // архивированная корзинка
        Basket::create(
            [
                'start_sum'              => 1000,
                'account_id'             => $this->userAccount->id,
                'currency_pair_id'       => $this->testCurrencyPair->id,
                'currency_1_last_amount' => null,
                'currency_2_last_amount' => null,
                'next_action'            => SELL_ACTION_CODE,
                'archive'                => true,
                'strategy'               => DEFAULT_TRADER,
            ]
        );
        Basket::create(
            [
                'start_sum'              => 1000,
                'account_id'             => $this->userAccount->id,
                'currency_pair_id'       => $this->testCurrencyPair->id,
                'currency_1_last_amount' => null,
                'currency_2_last_amount' => null,
                'next_action'            => SELL_ACTION_CODE,
                'archive'                => false,
                'strategy'               => DEFAULT_TRADER,
            ]
        );

        $baskets = Trade::getBaskets(
            $this->testCurrencyPair->id,
            SELL_ACTION_CODE,
            DEFAULT_TRADER
        );

        $this->assertEquals(2, count($baskets));
    }

    /**
     * @test
     *
     * @return void
     */
    public function itSavesTradersDecisions()
    {
        $this->assertEquals(
            0,
            TraderDecision::count()
        );

        Basket::create(
            [
                'start_sum'              => 1000,
                'account_id'             => $this->userAccount->id,
                'currency_pair_id'       => $this->testCurrencyPair->id,
                'currency_1_last_amount' => null,
                'currency_2_last_amount' => 1000,
                'next_action'            => BUY_ACTION_CODE,
                'strategy'               => 'AlwaysBuyTrader',
            ]
        );

        UpdateCurrencyRates::dispatchNow();

        // не проверяем на равенство 1, так как решения выставляются для каждой из валют, а их всегда больше 1
        $this->assertGreaterThan(
            0,
            TraderDecision::count()
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function itCreatesOrders()
    {
        $this->assertEquals(
            0,
            Order::count()
        );

        Basket::create(
            [
                'start_sum'              => 1000,
                'account_id'             => $this->userAccount->id,
                'currency_pair_id'       => $this->testCurrencyPair->id,
                'currency_1_last_amount' => null,
                'currency_2_last_amount' => 1000,
                'next_action'            => BUY_ACTION_CODE,
                'strategy'               => 'AlwaysBuyTrader',
            ]
        );

        UpdateCurrencyRates::dispatchNow();

        // не проверяем на равенство 1, так как решения выставляются для каждой из валют, а их всегда больше 1
        $this->assertGreaterThan(
            0,
            Order::count()
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function itCalculatesPairsMetrics()
    {
        $redisKeyForCurrencyPair = 'test.BTC.USD';

        $metricCodes = [
            'avg_buy_1',
            'avg_buy_2',
            'avg_buy_4',
            'avg_buy_6',
            'avg_buy_8',
            'avg_buy_12',
            'avg_buy_24',
            'avg_sell_1',
            'avg_sell_2',
            'avg_sell_4',
            'avg_sell_6',
            'avg_sell_8',
            'avg_sell_12',
            'avg_sell_24',
            'macd_1_2',
            'macd_avg_1_2_1',
        ];
        foreach ($metricCodes as $metricCode) {
            $this->assertEquals(
                0,
                Redis::llen($redisKeyForCurrencyPair.'.'.$metricCode),
                $metricCode.' failed at clearing'
            );
        }

        // набиваем бд тестовыми данными, чтобы средние считались
        $this->seed('TestExchangeMarketsDayRatesSeeder');

        UpdateCurrencyRates::dispatchNow();

        // не проверяем на равенство 1, так как решения выставляются для каждой из валют, а их всегда больше 1
        foreach ($metricCodes as $metricCode) {
            $this->assertGreaterThan(
                0,
                Redis::llen($redisKeyForCurrencyPair.'.'.$metricCode),
                $metricCode
            );
        }
    }
}