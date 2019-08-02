<?php

namespace Tests\Browser;

use App\Models\ExchangeMarketUserAccount;
use App\Models\User;
use App\Trading\CurrencyPairRate;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class UserExchangeMarketAccountTest extends DuskTestCase
{
    use DatabaseMigrations;

    function setUp()
    {
        parent::setUp();

        factory(ExchangeMarketUserAccount::class)->create();

        $this->browse(
            function (Browser $browser) {
                $browser->resize(1920, 1080);
                $browser->loginAs(User::find(1))->visit('/home');
            }
        );

        // нужна хотя бы одна запись о котировках
        CurrencyPairRate::save('test.BTC.USD', 1, 1, 1);
    }

    /**
     * @test
     */
    public function it_shows_user_balances()
    {
        $this->browse(
            function (Browser $browser) {
                $browser->assertSee('2.50005');
            }
        );
    }

    /**
     * @test
     */
    public function size_of_basket_subtracts_from_balances()
    {
        $this->browse(
            function (Browser $browser) {
                $browser->waitFor('[value="BTC"]')
                    ->select('currency_1', 'BTC')
                    ->select('currency_2', 'USD')
                    ->type('start_sum', '1')
                    ->press('create')
                    ->waitFor('.account-baskets__item')
                    ->assertSee('1.50005');
            }
        );
    }
}
