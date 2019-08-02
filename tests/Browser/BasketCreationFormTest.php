<?php

namespace Tests\Browser;

use App\Jobs\UpdateCurrencyRates;
use App\Models\Basket;
use App\Models\ExchangeMarketUserAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class BasketCreationFormTest extends DuskTestCase
{

    use DatabaseMigrations;

    function setUp()
    {
        parent::setUp();

        // нужно хотя бы одна котировка
        for ($i = 0; $i < 10; $i++) {
            UpdateCurrencyRates::dispatchNow();
        }

        factory(ExchangeMarketUserAccount::class)->create();

        $this->browse(
            function (Browser $browser) {
                $browser->resize(1920, 1080);
                $browser->loginAs(User::find(1))->visit('/home');
            }
        );
    }

    /**
     * @test
     */
    public function user_can_create_basket()
    {
        $this->browse(
            function (Browser $browser) {
                $browser->waitFor('[value="BTC"]')
                    ->select('currency_1', 'BTC')
                    ->select('currency_2', 'USD')
                    ->type('start_sum', '1')
                    ->press('create')
                    ->assertDontSee('Ошибка')
                    ->waitFor('.account-baskets__item')
                    ->assertSeeIn('.basket__title', '1 BTC')
                    ->assertSeeIn('.basket__text', 'BTC <-> USD');
            }
        );
    }

    /**
     * @test
     */
    public function user_cannot_create_basket_with_amount_less_than_allowed()
    {
        $this->browse(
            function (Browser $browser) {
                $browser->waitFor('[value="BTC"]')
                    ->select('currency_1', 'BTC')
                    ->select('currency_2', 'USD')
                    ->type('start_sum', '0.0001')
                    ->press('create')
                    ->waitFor('.account-baskets__item')
                    ->assertSeeIn('.basket__title', '0.001 BTC');
            }
        );
    }

    /**
     * @test
     */
    public function user_cannot_create_basket_with_amount_more_than_allowed()
    {
        $this->browse(
            function (Browser $browser) {
                $browser->waitFor('[value="BTC"]')
                    ->select('currency_1', 'BTC')
                    ->select('currency_2', 'USD')
                    ->type('start_sum', '1000')
                    ->press('create')
                    ->waitFor('.account-baskets__item')
                    ->assertSeeIn('.basket__title', '2.50005 BTC');
            }
        );
    }

    /**
     * @test
     */
    public function basket_creates_with_both_last_currencies_amount()
    {
        $this->browse(
            function (Browser $browser) {
                $browser->waitFor('[value="BTC"]')
                    ->select('currency_1', 'BTC')
                    ->select('currency_2', 'USD')
                    ->type('start_sum', '1')
                    ->press('create')
                    ->waitFor('.account-baskets__item');

                $basket = Basket::first();
                $this->assertGreaterThan(0, $basket->currency_1_last_amount);
                $this->assertGreaterThan(0, $basket->currency_2_last_amount);
            }
        );
    }
}
