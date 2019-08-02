<?php

namespace Tests\Browser;

use App\CurrencyPairsMetrics\Macd;
use App\Models\CurrencyPairTrend;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PastAnalysisTest extends DuskTestCase
{
    use DatabaseMigrations;

    public $testCurrencyPairID;
    public $adminGroupID = 1;

    function setUp()
    {
        parent::setUp();

        // набиваем бд тестовыми данными
        $this->seed('TestExchangeMarketsDayRatesSeeder');

        $this->browse(
            function (Browser $browser) {
                $browser->resize(1920, 1080);
            }
        );

        $this->testCurrencyPairID = 1; // 1 - Test BTC/USD
    }

    /**
     * @test
     */
    public function itPreparesBuyDataset()
    {
        $this->browse(
            function (Browser $browser) {
                // логинимся под админом
                $browser->loginAs(factory(User::class)->create(['group_id' => $this->adminGroupID]));

                $browser->visit('/oko/past-analysis')
                    ->select('#currency_pair', $this->testCurrencyPairID)
                    ->waitFor('#get_pair_info')
                    ->click('#get_pair_info')
                    ->waitFor('#show_graph')
                    ->click('#show_graph')
                    ->assertVue('datasets[0].label', 'Покупка', '@rates-graph');
            }
        );
    }

    /**
     * @test
     */
    public function itPreparesSellDataset()
    {
        $this->browse(
            function (Browser $browser) {
                // логинимся под админом
                $browser->loginAs(factory(User::class)->create(['group_id' => $this->testCurrencyPairID]));

                $browser->visit('/oko/past-analysis')
                    ->select('#currency_pair', $this->testCurrencyPairID)
                    ->waitFor('#get_pair_info')
                    ->click('#get_pair_info')
                    ->waitFor('#show_graph')
                    ->click('#show_graph')
                    ->assertVue('datasets[1].label', 'Продажа', '@rates-graph');
            }
        );
    }

    /**
     * @test
     */
    public function itPreparesTrendsDatasets()
    {
        $now = Carbon::today();
        $timestampNow = $now->timestamp;

        // создаём тестовые записи о трендах
        CurrencyPairTrend::create( // тренд, который начинается раньше периода, заканчивается после начала
            [
                'currency_pair_id' => $this->testCurrencyPairID,
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
        CurrencyPairTrend::create( // тренд, который находится внутри периода
            [
                'currency_pair_id' => $this->testCurrencyPairID,
                'type'             => 'test2',
                'lt_x'             => $timestampNow + MINUTES_IN_HOUR * SECONDS_IN_MINUTE,
                'lt_y'             => 1,
                'lb_x'             => $timestampNow + MINUTES_IN_HOUR * SECONDS_IN_MINUTE,
                'lb_y'             => 1,
                'rt_x'             => $timestampNow + MINUTES_IN_HOUR * SECONDS_IN_MINUTE * 3,
                'rt_y'             => 1,
                'rb_x'             => $timestampNow + MINUTES_IN_HOUR * SECONDS_IN_MINUTE * 3,
                'rb_y'             => 1,
            ]
        );
        CurrencyPairTrend::create( // тренд, который начинается во время периода, заканчивается после конца
            [
                'currency_pair_id' => $this->testCurrencyPairID,
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

        $this->browse(
            function (Browser $browser) {
                // логинимся под админом
                $browser->loginAs(factory(User::class)->create(['group_id' => $this->adminGroupID]));

                $browser->visit('/oko/past-analysis')
                    ->select('#currency_pair', $this->testCurrencyPairID)
                    ->waitFor('#get_pair_info')
                    ->click('#get_pair_info')
                    ->waitFor('#show_graph')
                    ->click('#show_graph')
                    ->assertVue('datasets[2].label', 'Тренд test1 top line', '@rates-graph')
                    ->assertVue('datasets[3].label', 'Тренд test1 bottom line', '@rates-graph')
                    ->assertVue('datasets[4].label', 'Тренд test2 top line', '@rates-graph')
                    ->assertVue('datasets[5].label', 'Тренд test2 bottom line', '@rates-graph')
                    ->assertVue('datasets[6].label', 'Тренд test3 top line', '@rates-graph')
                    ->assertVue('datasets[7].label', 'Тренд test3 bottom line', '@rates-graph');
            }
        );
    }

    /**
     * @test
     */
    public function itPreparesMacdDatasets()
    {
        $now = Carbon::today();
        $timestampNow = $now->timestamp;

        // создаём тестовые записи с метрикой
        Macd::store('test.BTC.USD', 1, 2, $timestampNow + MINUTES_IN_HOUR * SECONDS_IN_MINUTE, 3);

        $this->browse(
            function (Browser $browser) {
                // логинимся под админом
                $browser->loginAs(factory(User::class)->create(['group_id' => $this->adminGroupID]));

                $browser->visit('/oko/past-analysis')
                    ->select('#currency_pair', $this->testCurrencyPairID)
                    ->waitFor('#get_pair_info')
                    ->click('#get_pair_info')
                    ->waitFor('[name="Macd"]')
                    ->select('[name="Macd"]', 'macd_1_2')
                    ->waitFor('#show_graph')
                    ->click('#show_graph')
                    ->assertVue('datasets[2].label', 'macd_1_2', '@rates-graph')
                    ->assertVue('datasets[2].xAxisID', 'default-x-axis', '@rates-graph');
            }
        );
    }
}
