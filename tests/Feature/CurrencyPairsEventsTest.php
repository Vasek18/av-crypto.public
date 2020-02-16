<?php

namespace Tests\Feature;

use App\CurrencyPairsMetrics\Average;
use App\CurrencyPairsMetrics\Macd;
use App\CurrencyPairsMetrics\MacdAverage;
use App\Events\CurrencyPairRateChanged;
use App\Listeners\FireCurrencyPairEvents;
use App\Models\CurrencyPairEventObservation;
use App\Trading\CurrencyPairRate;
use App\Trading\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyPairsEventsTest extends TestCase
{
    use RefreshDatabase;

    protected $preserveGlobalState      = false;
    protected $runTestInSeparateProcess = true;

    public $user;
    public $userAccount;
    public $exchangeMarket;

    /**
     * @test
     *
     * @return void
     */
    public function itSavesEvents()
    {
        $currencyPairCode2 = 'test.XPR.USD';

        Event::fire($this->testCurrencyPair->code, 'test_event_1', 1, 2);
        Event::fire($this->testCurrencyPair->code, 'test_event_2', 1, 2);
        Event::fire($currencyPairCode2, 'test_event_1', 1, 2);

        $this->assertEquals(2, count(Event::get($this->testCurrencyPair->code)));
        $this->assertEquals(1, count(Event::get($currencyPairCode2)));
    }

    /**
     * @test
     *
     * @return void
     */
    public function ifAfterEventPriceIncreasesAtCertainPercentItNotesInObservations()
    {
        $pastPrice = 100;
        $currentPrice = $pastPrice * (CurrencyPairEventObservation::getThresholdPercent() + 1);

        Event::fire($this->testCurrencyPair->code, 'test_event_1', $currentPrice, $pastPrice);
        Event::fire($this->testCurrencyPair->code, 'test_event_2', $pastPrice, $currentPrice);

        CurrencyPairRate::save($this->testCurrencyPair->code, $currentPrice, $currentPrice, date('U'));
        CurrencyPairEventObservation::checkEvents();

        $this->assertEquals(1, CurrencyPairEventObservation::count());
        $this->assertEquals(1, CurrencyPairEventObservation::first()->top_hits);
    }

    /**
     * @test
     *
     * @return void
     */
    public function ifAfterEventPriceDecreasesAtCertainPercentItNotesInObservations()
    {
        $pastPrice = 100;
        $currentPrice = $pastPrice - (CurrencyPairEventObservation::getThresholdPercent() + 1);

        Event::fire($this->testCurrencyPair->code, 'test_event_1', $currentPrice, $pastPrice);
        Event::fire($this->testCurrencyPair->code, 'test_event_2', $pastPrice, $currentPrice);

        CurrencyPairRate::save($this->testCurrencyPair->code, $currentPrice, $currentPrice, date('U'));
        CurrencyPairEventObservation::checkEvents();

        $this->assertEquals(1, CurrencyPairEventObservation::count());
        $this->assertEquals(1, CurrencyPairEventObservation::first()->bottom_hits);
    }

    /**
     * @test
     *
     * @return void
     */
    public function ifAfterEventPriceDidntChangeAtCertainPercentItNotesInObservations()
    {
        $pastPrice = 100;
        $currentPrice = $pastPrice - (CurrencyPairEventObservation::getThresholdPercent() - 1);

        // Создаём события: одно не выходит за диапазон наблюдения, другое лежит прямо на границе. Заодно проверим, что событие записывается только после выхода за диапазон наблюдения по времени
        Event::save(
            $this->testCurrencyPair->code,
            'test_event_1',
            $currentPrice,
            $pastPrice,
            date('U') - CurrencyPairEventObservation::getPeriodInSeconds()
        );
        $randomSeconds = 100;
        Event::save(
            $this->testCurrencyPair->code,
            'test_event_2',
            $pastPrice,
            $currentPrice,
            date('U') - CurrencyPairEventObservation::getPeriodInSeconds() + $randomSeconds
        );

        // запускаем проверку событий
        CurrencyPairRate::save($this->testCurrencyPair->code, $currentPrice, $currentPrice, date('U'));
        CurrencyPairEventObservation::checkEvents();

        // записалось наблюдение только одного события. Записалось со статусом промахнувшегося события
        $this->assertEquals(1, CurrencyPairEventObservation::count());
        $this->assertEquals(1, CurrencyPairEventObservation::first()->missed);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itDeletesOldEvents()
    {
        // Создаём события: одно не выходит за диапазон наблюдения, другое лежит прямо на границе
        $price = 100;
        Event::save(
            $this->testCurrencyPair->code,
            'test_event_1',
            $price,
            $price,
            date('U') - CurrencyPairEventObservation::getPeriodInSeconds()
        );
        $randomSeconds = 100;
        Event::save(
            $this->testCurrencyPair->code,
            'test_event_2',
            $price,
            $price,
            date('U') - CurrencyPairEventObservation::getPeriodInSeconds() + $randomSeconds
        );

        // запускаем проверку событий
        CurrencyPairRate::save($this->testCurrencyPair->code, $price, $price, date('U'));
        CurrencyPairEventObservation::checkEvents();

        // осталось только одно событие
        $this->assertEquals(1, count(Event::get($this->testCurrencyPair->code)));
    }

    /**
     * @test
     *
     * @return void
     */
    public function eventDoesntNotesInObservationsTwice()
    {
        $this->assertEquals(0, CurrencyPairEventObservation::count());

        $pastPrice = 100;
        $currentPrice = $pastPrice * ((100 + (CurrencyPairEventObservation::getThresholdPercent() + 1)) / 100);

        Event::fire($this->testCurrencyPair->code, 'test_event_1', $currentPrice, $pastPrice);

        // проверяем события
        CurrencyPairRate::save($this->testCurrencyPair->code, $currentPrice, $currentPrice, date('U'));
        CurrencyPairEventObservation::checkEvents();

        // и второй раз проверяем
        CurrencyPairRate::save($this->testCurrencyPair->code, $currentPrice, $currentPrice, date('U'));
        CurrencyPairEventObservation::checkEvents();

        // у наблюдения будет записан только один хит
        $this->assertEquals(1, CurrencyPairEventObservation::count());
        $this->assertEquals(1, CurrencyPairEventObservation::first()->top_hits);
        $this->assertEquals(0, CurrencyPairEventObservation::first()->bottom_hits);
        $this->assertEquals(0, CurrencyPairEventObservation::first()->missed);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itFiresMacdCrossItsAverageUpDownEvent()
    {
        // сохраняем метрики, которые запустят событие
        Macd::store($this->testCurrencyPair->code, 1, 2, $this->timestampNow - SECONDS_IN_MINUTE * 2, 3);
        Macd::store($this->testCurrencyPair->code, 1, 2, $this->timestampNow - SECONDS_IN_MINUTE, 3);
        Macd::store($this->testCurrencyPair->code, 1, 2, $this->timestampNow, 2);
        MacdAverage::store($this->testCurrencyPair->code, 1, 2, 1, $this->timestampNow - SECONDS_IN_MINUTE * 2, 1);
        MacdAverage::store($this->testCurrencyPair->code, 1, 2, 1, $this->timestampNow - SECONDS_IN_MINUTE, 1);
        MacdAverage::store($this->testCurrencyPair->code, 1, 2, 1, $this->timestampNow, 2);

        // запускаем расчёт событий
        (new FireCurrencyPairEvents())->handle(
            new CurrencyPairRateChanged(
                $this->testCurrencyPair->id,
                $this->testCurrencyPair->code,
                CurrencyPairRate::save($this->testCurrencyPair->code, 1, 1, $this->timestampNow)
            )
        );

        // проверяем, что событие создалось с правильными параметрами
        $events = Event::get($this->testCurrencyPair->code);
        $this->assertEquals(1, count($events));
        $this->assertEquals('macd_cross_its_average', $events[0]['type']);
        $this->assertEquals(
            [
                'direction'      => 'up_down',
                'fast_period'    => 1,
                'slow_period'    => 2,
                'average_period' => 1,
            ],
            $events[0]['params']
        );
        $this->assertEquals('1', $events[0]['buy_price']);
        $this->assertEquals('1', $events[0]['sell_price']);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itFiresMacdCrossItsAverageDownUpEvent()
    {
        // сохраняем метрики, которые запустят событие
        Macd::store($this->testCurrencyPair->code, 2, 4, $this->timestampNow - SECONDS_IN_MINUTE * 2, 1);
        Macd::store($this->testCurrencyPair->code, 2, 4, $this->timestampNow - SECONDS_IN_MINUTE, 1);
        Macd::store($this->testCurrencyPair->code, 2, 4, $this->timestampNow, 2);
        MacdAverage::store($this->testCurrencyPair->code, 2, 4, 1, $this->timestampNow - SECONDS_IN_MINUTE * 2, 3);
        MacdAverage::store($this->testCurrencyPair->code, 2, 4, 1, $this->timestampNow - SECONDS_IN_MINUTE, 3);
        MacdAverage::store($this->testCurrencyPair->code, 2, 4, 1, $this->timestampNow, 2);

        // запускаем расчёт событий
        (new FireCurrencyPairEvents())->handle(
            new CurrencyPairRateChanged(
                $this->testCurrencyPair->id,
                $this->testCurrencyPair->code,
                CurrencyPairRate::save($this->testCurrencyPair->code, 2, 2, $this->timestampNow)
            )
        );

        // проверяем, что событие создалось
        $events = Event::get($this->testCurrencyPair->code);
        $this->assertEquals(1, count($events));
        $this->assertEquals('macd_cross_its_average', $events[0]['type']);
        $this->assertEquals(
            [
                'direction'      => 'down_up',
                'fast_period'    => 2,
                'slow_period'    => 4,
                'average_period' => 1,
            ],
            $events[0]['params']
        );
        $this->assertEquals(2, $events[0]['buy_price']);
        $this->assertEquals(2, $events[0]['sell_price']);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itDoesntFiresMacdCrossItsAverageEventIfGraphicsDontIntersect()
    {
        // сохраняем метрики, которые запустят событие
        Macd::store($this->testCurrencyPair->code, 1, 2, $this->timestampNow - SECONDS_IN_MINUTE * 2, 1);
        Macd::store($this->testCurrencyPair->code, 1, 2, $this->timestampNow - SECONDS_IN_MINUTE, 1);
        Macd::store($this->testCurrencyPair->code, 1, 2, $this->timestampNow, 2);
        MacdAverage::store($this->testCurrencyPair->code, 1, 2, 1, $this->timestampNow - SECONDS_IN_MINUTE * 2, 3);
        MacdAverage::store($this->testCurrencyPair->code, 1, 2, 1, $this->timestampNow - SECONDS_IN_MINUTE, 3);
        MacdAverage::store($this->testCurrencyPair->code, 1, 2, 1, $this->timestampNow, 4);

        // запускаем расчёт событий
        (new FireCurrencyPairEvents())->handle(
            new CurrencyPairRateChanged(
                $this->testCurrencyPair->id,
                $this->testCurrencyPair->code,
                CurrencyPairRate::save($this->testCurrencyPair->code, 1, 1, $this->timestampNow)
            )
        );

        // проверяем, что событие создалось
        $events = Event::get($this->testCurrencyPair->code);
        $this->assertEquals(0, count($events));
    }

    /**
     * @test
     *
     * @return void
     */
    public function itFiresRatesCrossItsAverageUpDownEvent()
    {
        // сохраняем метрики, которые запустят событие
        CurrencyPairRate::save($this->testCurrencyPair->code, 3, 3, $this->timestampNow - SECONDS_IN_MINUTE * 2);
        CurrencyPairRate::save($this->testCurrencyPair->code, 3, 3, $this->timestampNow - SECONDS_IN_MINUTE);
        $lastRate = CurrencyPairRate::save($this->testCurrencyPair->code, 2, 2, $this->timestampNow);
        Average::store($this->testCurrencyPair->code, 'buy', 1, $this->timestampNow - SECONDS_IN_MINUTE * 2, 1);
        Average::store($this->testCurrencyPair->code, 'buy', 1, $this->timestampNow - SECONDS_IN_MINUTE, 1);
        Average::store($this->testCurrencyPair->code, 'buy', 1, $this->timestampNow, 2);

        // запускаем расчёт событий
        (new FireCurrencyPairEvents())->handle(
            new CurrencyPairRateChanged(
                $this->testCurrencyPair->id,
                $this->testCurrencyPair->code,
                $lastRate
            )
        );

        // проверяем, что событие создалось
        $events = Event::get($this->testCurrencyPair->code);
        $this->assertEquals(1, count($events));
        $this->assertEquals('rates_cross_its_average', $events[0]['type']);
        $this->assertEquals(
            [
                'direction'      => 'up_down',
                'average_period' => 1,
            ],
            $events[0]['params']
        );
        $this->assertEquals(2, $events[0]['buy_price']);
        $this->assertEquals(2, $events[0]['sell_price']);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itFiresRatesCrossItsAverageDownUpEvent()
    {
        // сохраняем метрики, которые запустят событие
        CurrencyPairRate::save($this->testCurrencyPair->code, 3, 3, $this->timestampNow - SECONDS_IN_MINUTE * 2);
        CurrencyPairRate::save($this->testCurrencyPair->code, 3, 3, $this->timestampNow - SECONDS_IN_MINUTE);
        $lastRate = CurrencyPairRate::save($this->testCurrencyPair->code, 4, 4, $this->timestampNow);
        Average::store($this->testCurrencyPair->code, 'buy', 2, $this->timestampNow - SECONDS_IN_MINUTE * 2, 4);
        Average::store($this->testCurrencyPair->code, 'buy', 2, $this->timestampNow - SECONDS_IN_MINUTE, 4);
        Average::store($this->testCurrencyPair->code, 'buy', 2, $this->timestampNow, 4);

        // запускаем расчёт событий
        (new FireCurrencyPairEvents())->handle(
            new CurrencyPairRateChanged(
                $this->testCurrencyPair->id,
                $this->testCurrencyPair->code,
                $lastRate
            )
        );

        // проверяем, что событие создалось
        $events = Event::get($this->testCurrencyPair->code);
        $this->assertEquals(1, count($events));
        $this->assertEquals('rates_cross_its_average', $events[0]['type']);
        $this->assertEquals(
            [
                'direction'      => 'down_up',
                'average_period' => 2,
            ],
            $events[0]['params']
        );
        $this->assertEquals(4, $events[0]['buy_price']);
        $this->assertEquals(4, $events[0]['sell_price']);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itDoesntFiresRatesCrossItsAverageEventIfGraphicsDontIntersect()
    {
        // сохраняем метрики, которые запустят событие
        CurrencyPairRate::save($this->testCurrencyPair->code, 3, 3, $this->timestampNow - SECONDS_IN_MINUTE * 2);
        CurrencyPairRate::save($this->testCurrencyPair->code, 3, 3, $this->timestampNow - SECONDS_IN_MINUTE);
        $lastRate = CurrencyPairRate::save($this->testCurrencyPair->code, 4, 4, $this->timestampNow);
        Average::store($this->testCurrencyPair->code, 'buy', 2, $this->timestampNow - SECONDS_IN_MINUTE * 2, 5);
        Average::store($this->testCurrencyPair->code, 'buy', 2, $this->timestampNow - SECONDS_IN_MINUTE, 5);
        Average::store($this->testCurrencyPair->code, 'buy', 2, $this->timestampNow, 7);

        // запускаем расчёт событий
        (new FireCurrencyPairEvents())->handle(
            new CurrencyPairRateChanged(
                $this->testCurrencyPair->id,
                $this->testCurrencyPair->code,
                $lastRate
            )
        );

        // проверяем, что событие создалось
        $events = Event::get($this->testCurrencyPair->code);
        $this->assertEquals(0, count($events));
    }

    /**
     * @test
     *
     * @return void
     */
    public function itDoesntFiresRatesCrossItsAverageEventIfGraphicsWereIntersectedInLastTicks()
    {
        // сохраняем метрики, которые запустят событие
        CurrencyPairRate::save($this->testCurrencyPair->code, 4, 4, $this->timestampNow - SECONDS_IN_MINUTE * 2);
        CurrencyPairRate::save($this->testCurrencyPair->code, 3, 3, $this->timestampNow - SECONDS_IN_MINUTE);
        $lastRate = CurrencyPairRate::save($this->testCurrencyPair->code, 4, 4, $this->timestampNow);
        Average::store($this->testCurrencyPair->code, 'buy', 2, $this->timestampNow - SECONDS_IN_MINUTE * 2, 4);
        Average::store($this->testCurrencyPair->code, 'buy', 2, $this->timestampNow - SECONDS_IN_MINUTE, 4);
        Average::store($this->testCurrencyPair->code, 'buy', 2, $this->timestampNow, 4);

        // запускаем расчёт событий
        (new FireCurrencyPairEvents())->handle(
            new CurrencyPairRateChanged(
                $this->testCurrencyPair->id,
                $this->testCurrencyPair->code,
                $lastRate
            )
        );

        // проверяем, что событие создалось
        $events = Event::get($this->testCurrencyPair->code);
        $this->assertEquals(0, count($events));
    }

    /**
     * @test
     *
     * @return void
     */
    public function testIfThereWasEventFunction()
    {
        $currentTime = date('U');
        // тут важен порядок
        Event::save(
            $this->testCurrencyPair->code,
            'test_event_5',
            1,
            1,
            $currentTime - 30 * SECONDS_IN_MINUTE,
            ['ololo' => 2]
        );
        Event::save($this->testCurrencyPair->code, 'test_event_3', 1, 1, $currentTime - 30 * SECONDS_IN_MINUTE);
        Event::save($this->testCurrencyPair->code, 'test_event_2', 1, 1, $currentTime - 20 * SECONDS_IN_MINUTE);
        Event::save($this->testCurrencyPair->code, 'test_event_1', 1, 1, $currentTime - 10 * SECONDS_IN_MINUTE);

        $this->assertTrue(
            Event::ifThereWasEvent($this->testCurrencyPair->code, 'test_event_1', [], 11 * SECONDS_IN_MINUTE)
        );
        $this->assertFalse(
            Event::ifThereWasEvent($this->testCurrencyPair->code, 'test_event_2', [], 11 * SECONDS_IN_MINUTE)
        );
        $this->assertTrue(Event::ifThereWasEvent($this->testCurrencyPair->code, 'test_event_3'));
        $this->assertFalse(Event::ifThereWasEvent($this->testCurrencyPair->code, 'test_event_4'));
        $this->assertTrue(Event::ifThereWasEvent($this->testCurrencyPair->code, 'test_event_5', ['ololo' => 2]));
        $this->assertFalse(Event::ifThereWasEvent($this->testCurrencyPair->code, 'test_event_5', ['ololo' => 3]));
    }

    /**
     * @test
     *
     * @return void
     */
    public function testFiringRatesCrossItsAverageAndMacdsCrossItsAverageComplexEventRatesFirst()
    {
        $currentTime = date('U');
        $tooOldTimestamp = $currentTime - Event::getSimultaneousEventsTimePeriod() - SECONDS_IN_MINUTE;
        $notTooOldTimestamp = $currentTime - Event::getSimultaneousEventsTimePeriod() + SECONDS_IN_MINUTE;

        // старые обязательно должны быть другой длительности, иначе проверка количества не несёт смысла
        Event::save(
            $this->testCurrencyPair->code,
            'rates_cross_its_average',
            1,
            1,
            $tooOldTimestamp,
            [
                'direction'      => 'up_down',
                'average_period' => 2,
            ]
        );
        Event::save(
            $this->testCurrencyPair->code,
            'rates_cross_its_average',
            1,
            1,
            $tooOldTimestamp,
            [
                'direction'      => 'down_up',
                'average_period' => 1,
            ]
        );
        Event::save(
            $this->testCurrencyPair->code,
            'rates_cross_its_average',
            1,
            1,
            $notTooOldTimestamp,
            [
                'direction'      => 'up_down',
                'average_period' => 2,
            ]
        );
        Event::save(
            $this->testCurrencyPair->code,
            'rates_cross_its_average',
            1,
            1,
            $notTooOldTimestamp,
            [
                'direction'      => 'down_up',
                'average_period' => 1,
            ]
        );

        // одно комплексное событие должно создастся для этого события
        Event::save(
            $this->testCurrencyPair->code,
            'macd_cross_its_average',
            1,
            1,
            $currentTime,
            [
                'direction'      => 'up_down',
                'fast_period'    => 1,
                'slow_period'    => 2,
                'average_period' => 1,
            ]
        );
        // одно комплексное событие должно создастся для этого события
        Event::save(
            $this->testCurrencyPair->code,
            'macd_cross_its_average',
            1,
            1,
            $currentTime,
            [
                'direction'      => 'down_up',
                'fast_period'    => 1,
                'slow_period'    => 2,
                'average_period' => 1,
            ]
        );

        // проверяем, что события создались и не дублировались
        $events = Event::get($this->testCurrencyPair->code);
        $this->assertEquals(8, count($events)); // 4 + 2 + 2
        $this->assertEquals('macd_and_rates_cross_its_averages', $events[5]['type']);
        $this->assertEquals(
            [
                'direction'           => 'up_down',
                'rates_period'        => 2,
                'macd_fast_period'    => 1,
                'macd_slow_period'    => 2,
                'macd_average_period' => 1,
            ],
            $events[5]['params']
        );

        $this->assertEquals('macd_and_rates_cross_its_averages', $events[7]['type']);
        $this->assertEquals(
            [
                'direction'           => 'down_up',
                'rates_period'        => 1,
                'macd_fast_period'    => 1,
                'macd_slow_period'    => 2,
                'macd_average_period' => 1,
            ],
            $events[7]['params']
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function testFiringRatesCrossItsAverageAndMacdsCrossItsAverageComplexEventMacdsFirst()
    {
        $currentTime = date('U');
        $tooOldTimestamp = $currentTime - Event::getSimultaneousEventsTimePeriod() - SECONDS_IN_MINUTE;
        $notTooOldTimestamp = $currentTime - Event::getSimultaneousEventsTimePeriod() + SECONDS_IN_MINUTE;

        // старые обязательно должны быть другой длительности, иначе проверка количества не несёт смысла
        Event::save(
            $this->testCurrencyPair->code,
            'macd_cross_its_average',
            1,
            1,
            $tooOldTimestamp,
            [ // тонко, должно всегда быть 1 12 1, иначе тест не имеет смысла
                'direction'      => 'up_down',
                'fast_period'    => 1,
                'slow_period'    => 12,
                'average_period' => 1,
            ]
        );
        Event::save(
            $this->testCurrencyPair->code,
            'macd_cross_its_average',
            1,
            1,
            $tooOldTimestamp,
            [ // тонко, должно всегда быть 1 12 1, иначе тест не имеет смысла
                'direction'      => 'down_up',
                'fast_period'    => 1,
                'slow_period'    => 12,
                'average_period' => 1,
            ]
        );
        Event::save(
            $this->testCurrencyPair->code,
            'macd_cross_its_average',
            1,
            1,
            $notTooOldTimestamp,
            [
                'direction'      => 'up_down',
                'fast_period'    => 1,
                'slow_period'    => 2,
                'average_period' => 1,
            ]
        );
        Event::save(
            $this->testCurrencyPair->code,
            'macd_cross_its_average',
            1,
            1,
            $notTooOldTimestamp,
            [
                'direction'      => 'down_up',
                'fast_period'    => 1,
                'slow_period'    => 2,
                'average_period' => 1,
            ]
        );

        // одно комплексное событие должно создастся для этого события
        Event::save(
            $this->testCurrencyPair->code,
            'rates_cross_its_average',
            1,
            1,
            $currentTime,
            [
                'direction'      => 'up_down',
                'average_period' => 1,
            ]
        );
        // одно комплексное событие должно создастся для этого события
        Event::save(
            $this->testCurrencyPair->code,
            'rates_cross_its_average',
            1,
            1,
            $currentTime,
            [
                'direction'      => 'down_up',
                'average_period' => 2,
            ]
        );

        // проверяем, что события создались и не дублировались
        $events = Event::get($this->testCurrencyPair->code);
        $this->assertEquals(8, count($events));
        $this->assertEquals('macd_and_rates_cross_its_averages', $events[5]['type']);
        $this->assertEquals(
            [
                'direction'           => 'up_down',
                'rates_period'        => 1,
                'macd_fast_period'    => 1,
                'macd_slow_period'    => 2,
                'macd_average_period' => 1,
            ],
            $events[5]['params']
        );

        $this->assertEquals('macd_and_rates_cross_its_averages', $events[7]['type']);
        $this->assertEquals(
            [
                'direction'           => 'down_up',
                'rates_period'        => 2,
                'macd_fast_period'    => 1,
                'macd_slow_period'    => 2,
                'macd_average_period' => 1,
            ],
            $events[7]['params']
        );
    }
}