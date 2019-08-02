<?php

namespace Tests\Jobs;

use App\Jobs\ClearDB;
use App\Models\CurrencyPairTrend;
use App\Trading\CurrencyPairRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ClearDbTest extends TestCase
{

    use RefreshDatabase;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;

    public function setUp()
    {
        parent::setUp();

        // чистим редис перед каждым тестом
        Redis::flushall();
    }

    /**
     * @test
     *
     * @return void
     */
    public function itClearsOldCurrencyPairsRates()
    {
        $currentTimestamp = date('U');

        $currencyPairCode = 'test.BTC.USD';

        // забиваем тестовые данные
        $overheadInSeconds = 420;
        for ($i = 0; $i < SECONDS_TO_KEEP_RATES_IN_DB_FOR_TEST + $overheadInSeconds; $i = $i + SECONDS_IN_MINUTE) {
            CurrencyPairRate::save($currencyPairCode, 1, 1, $currentTimestamp - $i);
        }

        // должно быть 2 котировки
        $this->assertEquals(12, CurrencyPairRate::count($currencyPairCode));

        // запускаем очистку
        ClearDB::dispatchNow();

        // должно быть одна котировка
        $this->assertEquals(5, CurrencyPairRate::count($currencyPairCode));
    }

    /**
     * @test
     */
    public function itClearsOldCurrencyPairsMetrics()
    {
        $currentTimestamp = date('U');

        // заполняем
        $metricRedisCodes = [
            'avg_buy_1',
            'avg_sell_1',
            'avg_buy_2',
            'avg_sell_2',
            'minimum_30',
            'maximum_30',
            'macd_1_2',
            'macd_1_24',
            'macd_avg_1_2_1',
            'sell_quantity',
            'buy_amount',
            'spread',
        ];
        $redisKeyForCurrencyPair = 'test.BTC.USD';
        $overheadInSeconds = 420;
        for ($i = 0; $i < SECONDS_TO_KEEP_RATES_IN_DB_FOR_TEST + $overheadInSeconds; $i = $i + SECONDS_IN_MINUTE) {
            foreach ($metricRedisCodes as $c => $metricRedisCode) {
                // будем заполнять явно, так как тестим влияние на сервер
                Redis::lpush( // тут надо добавлять слева, так как мы уменьшаем таймстемп внутри цикла, то есть каждая следующая итерация создаёт более старую метрику
                    $redisKeyForCurrencyPair.'.'.$metricRedisCode,
                    serialize(
                        [
                            'timestamp' => $currentTimestamp - $i,
                            'value'     => $c, // просто какое-то число, мы не это проверяем
                        ]
                    )
                );
            }
        }

        // проверка для спокойствия, что тесту можно доверять
        foreach ($metricRedisCodes as $metricRedisCode) {
            $this->assertEquals(12, Redis::llen($redisKeyForCurrencyPair.'.'.$metricRedisCode));
        }

        // запускаем очистку
        ClearDB::dispatchNow();

        // проверяем, что старые значения удалились
        foreach ($metricRedisCodes as $metricRedisCode) {
            $this->assertEquals(
                5,
                Redis::llen($redisKeyForCurrencyPair.'.'.$metricRedisCode),
                $metricRedisCode.' failed at clearing'
            );
        }
    }

    /**
     * @test
     */
    public function itClearsOldCurrencyPairsTrends()
    {
        $currentTimestamp = date('U');

        // создаём тестовые записи о трендах
        CurrencyPairTrend::create( // старый
            [
                'currency_pair_id' => 1,
                'type'             => 'test1',
                'lt_x'             => $currentTimestamp - SECONDS_TO_KEEP_RATES_IN_DB_FOR_TEST - 100,
                'lt_y'             => 1,
                'lb_x'             => $currentTimestamp - SECONDS_TO_KEEP_RATES_IN_DB_FOR_TEST - 100,
                'lb_y'             => 1,
                'rt_x'             => $currentTimestamp - SECONDS_TO_KEEP_RATES_IN_DB_FOR_TEST - 100,
                'rt_y'             => 1,
                'rb_x'             => $currentTimestamp - SECONDS_TO_KEEP_RATES_IN_DB_FOR_TEST - 100,
                'rb_y'             => 1,
            ]
        );
        CurrencyPairTrend::create( // на грани
            [
                'currency_pair_id' => 1,
                'type'             => 'test2',
                'lt_x'             => $currentTimestamp - SECONDS_TO_KEEP_RATES_IN_DB_FOR_TEST,
                'lt_y'             => 1,
                'lb_x'             => $currentTimestamp - SECONDS_TO_KEEP_RATES_IN_DB_FOR_TEST,
                'lb_y'             => 1,
                'rt_x'             => $currentTimestamp - SECONDS_TO_KEEP_RATES_IN_DB_FOR_TEST,
                'rt_y'             => 1,
                'rb_x'             => $currentTimestamp - SECONDS_TO_KEEP_RATES_IN_DB_FOR_TEST,
                'rb_y'             => 1,
            ]
        );
        CurrencyPairTrend::create( // на грани
            [
                'currency_pair_id' => 1,
                'type'             => 'test3',
                'lt_x'             => $currentTimestamp - SECONDS_TO_KEEP_RATES_IN_DB_FOR_TEST + 50,
                'lt_y'             => 1,
                'lb_x'             => $currentTimestamp - SECONDS_TO_KEEP_RATES_IN_DB_FOR_TEST + 50,
                'lb_y'             => 1,
                'rt_x'             => $currentTimestamp - SECONDS_TO_KEEP_RATES_IN_DB_FOR_TEST + 50,
                'rt_y'             => 1,
                'rb_x'             => $currentTimestamp - SECONDS_TO_KEEP_RATES_IN_DB_FOR_TEST + 50,
                'rb_y'             => 1,
            ]
        );

        // запускаем очистку
        ClearDB::dispatchNow();

        $this->assertEquals(2, CurrencyPairTrend::count());
    }
}