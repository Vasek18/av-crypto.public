<?php

namespace Tests\Jobs;

use App\Jobs\ClearDB;
use App\Models\Metrics\MetricsValue;
use App\Models\TraderDecision;
use App\Trading\CurrencyPairRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ClearDbTest extends TestCase
{
    use RefreshDatabase;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;

    /**
     * @test
     *
     * @return void
     */
    public function itClearsOldCurrencyPairsRates()
    {
        // забиваем тестовые данные
        for ($i = 0; $i < SECONDS_TO_KEEP_RATES_IN_DB * 2; $i = $i + SECONDS_IN_MINUTE) { // создаём в два раза больше котировок, чем храним
            CurrencyPairRate::save($this->testCurrencyPair->code, 1, 1, $this->timestampNow - $i);
        }

        // котировки собираются каждую минуту, поэтому их количество сейчас = дозволенное количество минут для хранения метрик + минуты тестового превышения
        $this->assertEquals(
            (SECONDS_TO_KEEP_RATES_IN_DB * 2) / SECONDS_IN_MINUTE,
            CurrencyPairRate::count($this->testCurrencyPair->code)
        );

        // запускаем очистку
        ClearDB::dispatchNow();

        // проверяем, что тестовые удалились
        $this->assertEquals(
            SECONDS_TO_KEEP_RATES_IN_DB / SECONDS_IN_MINUTE,
            CurrencyPairRate::count($this->testCurrencyPair->code)
        );
    }

    /**
     * @test
     */
    public function itClearsOldCurrencyPairsMetrics()
    {
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
        $minutesOfOverhead = 8;
        $overheadInSeconds = $minutesOfOverhead * SECONDS_IN_MINUTE;
        for ($i = 0; $i < SECONDS_TO_KEEP_RATES_IN_DB + $overheadInSeconds; $i = $i + SECONDS_IN_MINUTE) {
            foreach ($metricRedisCodes as $c => $metricRedisCode) {
                // будем заполнять явно, так как тестим влияние на сервер
                Redis::lpush( // тут надо добавлять слева, так как мы уменьшаем таймстемп внутри цикла, то есть каждая следующая итерация создаёт более старую метрику
                    $this->testCurrencyPair->code.'.'.$metricRedisCode,
                    serialize(
                        [
                            'timestamp' => $this->timestampNow - $i,
                            'value'     => $c, // просто какое-то число, мы не это проверяем
                        ]
                    )
                );
            }
        }

        // проверка для спокойствия, что тесту можно доверять
        foreach ($metricRedisCodes as $metricRedisCode) {
            $this->assertEquals(
                (SECONDS_TO_KEEP_RATES_IN_DB + $overheadInSeconds) / SECONDS_IN_MINUTE,
                Redis::llen($this->testCurrencyPair->code.'.'.$metricRedisCode)
            );
        }

        // запускаем очистку
        ClearDB::dispatchNow();

        // проверяем, что старые значения удалились
        foreach ($metricRedisCodes as $metricRedisCode) {
            $this->assertEquals(
                SECONDS_TO_KEEP_RATES_IN_DB / SECONDS_IN_MINUTE,
                Redis::llen($this->testCurrencyPair->code.'.'.$metricRedisCode),
                $metricRedisCode.' failed at clearing'
            );
        }
    }

    /**
     * @test
     *
     * @return void
     */
    public function itClearsOldTradersDecisions()
    {
        // забиваем тестовые данные
        TraderDecision::create(
            [
                'currency_pair_id' => $this->testCurrencyPair->id,
                'trader_code'      => 'test',
                'decision'         => 'S',
                'timestamp'        => $this->timestampNow - SECONDS_TO_KEEP_RATES_IN_DB - SECONDS_IN_MINUTE,
            ]
        );
        TraderDecision::create(
            [
                'currency_pair_id' => $this->testCurrencyPair->id,
                'trader_code'      => 'test',
                'decision'         => 'B',
                'timestamp'        => $this->timestampNow - SECONDS_TO_KEEP_RATES_IN_DB + SECONDS_IN_MINUTE,
            ]
        );

        // проверяем, что решения в принципе создались
        $this->assertEquals(
            2,
            TraderDecision::where('trader_code', 'test')->count()
        );

        // запускаем очистку
        ClearDB::dispatchNow();

        // проверяем, что тестовые удалились
        $this->assertEquals(
            1,
            TraderDecision::where('trader_code', 'test')->count()
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function itClearsOldSiteMetricsValues()
    {
        // забиваем тестовые данные
        MetricsValue::create(
            [
                'metrics_id' => 1,
                'value'      => 1,
                'decision'   => 1,
                'timestamp'  => $this->timestampNow - SECONDS_TO_KEEP_METRICS_IN_DB - SECONDS_IN_MINUTE,
            ]
        );
        MetricsValue::create(
            [
                'metrics_id' => 1,
                'value'      => 1,
                'counter'    => 1,
                'timestamp'  => $this->timestampNow - SECONDS_TO_KEEP_METRICS_IN_DB + SECONDS_IN_MINUTE,
            ]
        );

        // проверяем, что метрики в принципе создались
        $this->assertEquals(
            2,
            MetricsValue::where('metrics_id', 1)->count()
        );

        // запускаем очистку
        ClearDB::dispatchNow();

        // проверяем, что тестовые удалились
        $this->assertEquals(
            1,
            MetricsValue::where('metrics_id', 1)->count()
        );
    }
}