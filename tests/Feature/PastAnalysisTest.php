<?php

namespace Tests\Feature;

use App\Models\CurrencyPairTrend;
use App\Models\ExchangeMarket;
use App\Models\ExchangeMarketCurrencyPair;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class PastAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;

    public $exchangeMarket;
    public $currencyPair;
    public $now;

    public function setUp()
    {
        parent::setUp();

        // чистим редис перед каждым тестом
        Redis::flushall();

        // логинимся под админа
        $this->actingAs(factory(\App\Models\User::class)->create(['group_id' => 1]));

        // набиваем бд тестовыми данными
        $this->seed('TestExchangeMarketsDayRatesSeeder');

        // параметры для запроса
        $this->exchangeMarket = ExchangeMarket::where('code', 'test')->first();
        $this->currencyPair = ExchangeMarketCurrencyPair
            ::where('exchange_market_id', $this->exchangeMarket->id)
            ->where('currency_1_code', 'BTC')
            ->where('currency_2_code', 'USD')
            ->first();

        $this->now = Carbon::today();
    }

    /** @test */
    public function itReturnsRatesRelatedToTimePeriod()
    {
        $response = $this->json(
            'GET',
            'oko/past-analysis/get-pair-info',
            [
                'currency_pair_id'   => $this->currencyPair->id,
                'currency_1_code'    => $this->currencyPair->currency_1_code,
                'currency_2_code'    => $this->currencyPair->currency_2_code,
                'exchange_market_id' => $this->exchangeMarket->id,
                'dateFrom'           => $this->now->toDayDateTimeString(),
                'dateTo'             => $this->now->addDay()->toDayDateTimeString(),
            ]
        );

        // проверяем, что котировки возвращаются в нужном количестве
        $responseArray = $response->decodeResponseJson();
        $neededRatesCount = HOURS_PER_DAY * MINUTES_IN_HOUR; // сутки
        $this->assertCount($neededRatesCount, $responseArray['rates']);
    }

    /** @test */
    public function itReturnsTrendsRelatedToTimePeriod()
    {
        $timestampNow = $this->now->timestamp;

        // создаём тестовые записи о трендах
        CurrencyPairTrend::create( // тренд, который начинается раньше периода, заканчивается после начала
            [
                'currency_pair_id' => $this->currencyPair->id,
                'type'             => 'test1',
                'lt_x'             => $timestampNow - MINUTES_IN_HOUR * SECONDS_IN_MINUTE,
                'lt_y'             => 1,
                'lb_x'             => $timestampNow - MINUTES_IN_HOUR * SECONDS_IN_MINUTE,
                'lb_y'             => 1,
                'rt_x'             => $timestampNow + MINUTES_IN_HOUR * SECONDS_IN_MINUTE,
                'rt_y'             => 1,
                'rb_x'             => $timestampNow + MINUTES_IN_HOUR * SECONDS_IN_MINUTE,
                'rb_y'             => 1,
            ]
        );
        CurrencyPairTrend::create( // тренд, который заканчивается до начала
            [
                'currency_pair_id' => $this->currencyPair->id,
                'type'             => 'test2',
                'lt_x'             => $timestampNow - MINUTES_IN_HOUR * SECONDS_IN_MINUTE * 2,
                'lt_y'             => 1,
                'lb_x'             => $timestampNow - MINUTES_IN_HOUR * SECONDS_IN_MINUTE * 2,
                'lb_y'             => 1,
                'rt_x'             => $timestampNow - MINUTES_IN_HOUR * SECONDS_IN_MINUTE,
                'rt_y'             => 1,
                'rb_x'             => $timestampNow - MINUTES_IN_HOUR * SECONDS_IN_MINUTE,
                'rb_y'             => 1,
            ]
        );
        CurrencyPairTrend::create( // тренд, который начинается во время периода, заканчивается после конца
            [
                'currency_pair_id' => $this->currencyPair->id,
                'type'             => 'test3',
                'lt_x'             => $timestampNow + MINUTES_IN_HOUR * SECONDS_IN_MINUTE * 23,
                'lt_y'             => 1,
                'lb_x'             => $timestampNow + MINUTES_IN_HOUR * SECONDS_IN_MINUTE * 23,
                'lb_y'             => 1,
                'rt_x'             => $timestampNow + MINUTES_IN_HOUR * SECONDS_IN_MINUTE * 25,
                'rt_y'             => 1,
                'rb_x'             => $timestampNow + MINUTES_IN_HOUR * SECONDS_IN_MINUTE * 25,
                'rb_y'             => 1,
            ]
        );
        CurrencyPairTrend::create( // тренд, который начинается после периода
            [
                'currency_pair_id' => $this->currencyPair->id,
                'type'             => 'test4',
                'lt_x'             => $timestampNow + MINUTES_IN_HOUR * SECONDS_IN_MINUTE * 25,
                'lt_y'             => 1,
                'lb_x'             => $timestampNow + MINUTES_IN_HOUR * SECONDS_IN_MINUTE * 25,
                'lb_y'             => 1,
                'rt_x'             => $timestampNow + MINUTES_IN_HOUR * SECONDS_IN_MINUTE * 27,
                'rt_y'             => 1,
                'rb_x'             => $timestampNow + MINUTES_IN_HOUR * SECONDS_IN_MINUTE * 27,
                'rb_y'             => 1,
            ]
        );

        $response = $this->json(
            'GET',
            'oko/past-analysis/get-pair-info',
            [
                'currency_pair_id'   => $this->currencyPair->id,
                'currency_1_code'    => $this->currencyPair->currency_1_code,
                'currency_2_code'    => $this->currencyPair->currency_2_code,
                'exchange_market_id' => $this->exchangeMarket->id,
                'dateFrom'           => $this->now->toDayDateTimeString(),
                'dateTo'             => $this->now->addDay()->toDayDateTimeString(),
            ]
        );

        // проверяем, что возращаются нужные тренды
        $response->assertJson(['trends' => [['type' => 'test1'], ['type' => 'test3']]]);
        $responseArray = $response->decodeResponseJson();
        $this->assertCount(2, $responseArray['trends']); // трендов должно быть 2
    }

    /** @test */
    public function itReturnsMetricsRelatedToTimePeriod()
    {
        // набиваем редис метриками - напрямую оказалось нагляднее и помогло при переезде
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

        $firstMetricValueTimestamp = $this->now->timestamp;
        $firstMetricValueValue = 1;
        $secondMetricValueTimestamp = $this->now->timestamp + SECONDS_IN_MINUTE;
        $secondMetricValueValue = 2;
        foreach ($metricRedisCodes as $c => $metricRedisCode) {
            // забьём пару значений
            Redis::rpush(
                $this->currencyPair->code.'.'.$metricRedisCode,
                serialize(
                    [
                        'timestamp' => $firstMetricValueTimestamp,
                        'value'     => $firstMetricValueValue,
                    ]
                )
            );
            Redis::rpush(
                $this->currencyPair->code.'.'.$metricRedisCode,
                serialize(
                    [
                        'timestamp' => $secondMetricValueTimestamp,
                        'value'     => $secondMetricValueValue,
                    ]
                )
            );
        }

        $response = $this->json(
            'GET',
            'oko/past-analysis/get-pair-info',
            [
                'currency_pair_id'   => $this->currencyPair->id,
                'currency_1_code'    => $this->currencyPair->currency_1_code,
                'currency_2_code'    => $this->currencyPair->currency_2_code,
                'exchange_market_id' => $this->exchangeMarket->id,
                'dateFrom'           => $this->now->toDayDateTimeString(),
                'dateTo'             => $this->now->addDay()->toDayDateTimeString(),
            ]
        );

        // проверяем, что возращаются нужные тренды
        $response->assertJson(
            [
                'metrics' => [
                    [
                        'code'       => 'minimum',
                        'type'       => 'extremum',
                        'name'       => 'Минимум',
                        'group_name' => 'Максимумы/минимумы',
                        'values'     => [
                            [
                                'timestamp' => $firstMetricValueTimestamp,
                                'value'     => $firstMetricValueValue,

                            ],
                            [
                                'timestamp' => $secondMetricValueTimestamp,
                                'value'     => $secondMetricValueValue,

                            ],
                        ],
                    ],
                    [
                        'code'       => 'maximum',
                        'type'       => 'extremum',
                        'name'       => 'Максимум',
                        'group_name' => 'Максимумы/минимумы',
                        'values'     => [
                            [
                                'timestamp' => $firstMetricValueTimestamp,
                                'value'     => $firstMetricValueValue,

                            ],
                            [
                                'timestamp' => $secondMetricValueTimestamp,
                                'value'     => $secondMetricValueValue,

                            ],
                        ],
                    ],
                    [
                        'code'       => 'average_sell_1',
                        'type'       => 'average',
                        'name'       => 'за 1 час. Продажа',
                        'group_name' => 'Средние',
                        'values'     => [
                            [
                                'timestamp' => $firstMetricValueTimestamp,
                                'value'     => $firstMetricValueValue,

                            ],
                            [
                                'timestamp' => $secondMetricValueTimestamp,
                                'value'     => $secondMetricValueValue,

                            ],
                        ],
                    ],
                    [
                        'code'       => 'average_buy_1',
                        'type'       => 'average',
                        'name'       => 'за 1 час. Покупка',
                        'group_name' => 'Средние',
                        'values'     => [
                            [
                                'timestamp' => $firstMetricValueTimestamp,
                                'value'     => $firstMetricValueValue,

                            ],
                            [
                                'timestamp' => $secondMetricValueTimestamp,
                                'value'     => $secondMetricValueValue,

                            ],
                        ],
                    ],
                    [
                        'code'       => 'average_sell_2',
                        'type'       => 'average',
                        'name'       => 'за 2 часа. Продажа',
                        'group_name' => 'Средние',
                        'values'     => [
                            [
                                'timestamp' => $firstMetricValueTimestamp,
                                'value'     => $firstMetricValueValue,

                            ],
                            [
                                'timestamp' => $secondMetricValueTimestamp,
                                'value'     => $secondMetricValueValue,

                            ],
                        ],
                    ],
                    [
                        'code'       => 'average_buy_2',
                        'type'       => 'average',
                        'name'       => 'за 2 часа. Покупка',
                        'group_name' => 'Средние',
                        'values'     => [
                            [
                                'timestamp' => $firstMetricValueTimestamp,
                                'value'     => $firstMetricValueValue,

                            ],
                            [
                                'timestamp' => $secondMetricValueTimestamp,
                                'value'     => $secondMetricValueValue,

                            ],
                        ],
                    ],
                    [
                        'code'       => 'macd_1_24',
                        'type'       => 'macd',
                        'name'       => 'за 1/24 часа',
                        'group_name' => 'Macd',
                        'values'     => [
                            [
                                'timestamp' => $firstMetricValueTimestamp,
                                'value'     => $firstMetricValueValue,

                            ],
                            [
                                'timestamp' => $secondMetricValueTimestamp,
                                'value'     => $secondMetricValueValue,

                            ],
                        ],
                    ],
                    [
                        'code'       => 'macd_1_2',
                        'type'       => 'macd',
                        'name'       => 'за 1/2 часа',
                        'group_name' => 'Macd',
                        'values'     => [
                            [
                                'timestamp' => $firstMetricValueTimestamp,
                                'value'     => $firstMetricValueValue,

                            ],
                            [
                                'timestamp' => $secondMetricValueTimestamp,
                                'value'     => $secondMetricValueValue,

                            ],
                        ],
                    ],
                    [
                        'code'       => 'macd_average_1_2_1',
                        'type'       => 'macd',
                        'name'       => 'за 1/2/1 часа',
                        'group_name' => 'Средние по macd',
                        'values'     => [
                            [
                                'timestamp' => $firstMetricValueTimestamp,
                                'value'     => $firstMetricValueValue,

                            ],
                            [
                                'timestamp' => $secondMetricValueTimestamp,
                                'value'     => $secondMetricValueValue,

                            ],
                        ],
                    ],
                    [
                        'code'       => 'sell_quantity',
                        'type'       => 'sell_quantity',
                        'name'       => 'Объём продажи',
                        'group_name' => 'Стакан',
                        'values'     => [
                            [
                                'timestamp' => $firstMetricValueTimestamp,
                                'value'     => $firstMetricValueValue,

                            ],
                            [
                                'timestamp' => $secondMetricValueTimestamp,
                                'value'     => $secondMetricValueValue,

                            ],
                        ],
                    ],
                    [
                        'code'       => 'buy_amount',
                        'type'       => 'buy_amount',
                        'name'       => 'Объём покупки',
                        'group_name' => 'Стакан',
                        'values'     => [
                            [
                                'timestamp' => $firstMetricValueTimestamp,
                                'value'     => $firstMetricValueValue,

                            ],
                            [
                                'timestamp' => $secondMetricValueTimestamp,
                                'value'     => $secondMetricValueValue,

                            ],
                        ],
                    ],
                    [
                        'code'       => 'spread',
                        'type'       => 'spread',
                        'name'       => 'Спред',
                        'group_name' => 'Стакан',
                        'values'     => [
                            [
                                'timestamp' => $firstMetricValueTimestamp,
                                'value'     => $firstMetricValueValue,

                            ],
                            [
                                'timestamp' => $secondMetricValueTimestamp,
                                'value'     => $secondMetricValueValue,

                            ],
                        ],
                    ],
                ],
            ]
        );
    }
}