<?php

namespace Tests\Browser;

use App\CurrencyPairsMetrics\Macd;
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
