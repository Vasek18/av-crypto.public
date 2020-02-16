<?php

namespace Tests\Feature;

use App\Models\ExchangeMarket;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class IndexPageTest extends TestCase
{
    use RefreshDatabase;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;

    public $exchangeMarket;
    public $now;

    public function setUp()
    {
        parent::setUp();


        // набиваем бд тестовыми данными
        $this->seed('TestExchangeMarketsDayRatesSeeder');

        // параметры для запроса
        $this->exchangeMarket = ExchangeMarket::where('code', 'test')->first();

        $this->now = Carbon::today();
    }

    /** @test */
    public function itReturnsRatesRelatedToTimePeriod()
    {
        $response = $this->json(
            'GET',
            '/get-pair-info',
            [
                'currency_pair_id'   => $this->testCurrencyPair->id,
                'currency_1_code'    => $this->testCurrencyPair->currency_1_code,
                'currency_2_code'    => $this->testCurrencyPair->currency_2_code,
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
                $this->testCurrencyPair->code.'.'.$metricRedisCode,
                serialize(
                    [
                        'timestamp' => $firstMetricValueTimestamp,
                        'value'     => $firstMetricValueValue,
                    ]
                )
            );
            Redis::rpush(
                $this->testCurrencyPair->code.'.'.$metricRedisCode,
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
            '/get-pair-info',
            [
                'currency_pair_id'   => $this->testCurrencyPair->id,
                'currency_1_code'    => $this->testCurrencyPair->currency_1_code,
                'currency_2_code'    => $this->testCurrencyPair->currency_2_code,
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