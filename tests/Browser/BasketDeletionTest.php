<?php

namespace Tests\Browser;

use App\Jobs\UpdateCurrencyRates;
use App\Models\ExchangeMarketUserAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class BasketDeletionTest extends DuskTestCase
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

        // создаём корзинку
        $this->browse(
            function (Browser $browser) {
                $browser->waitFor('[value="BTC"]')
                    ->select('currency_1', 'BTC')
                    ->select('currency_2', 'USD')
                    ->type('start_sum', '1')
                    ->press('create');
            }
        );
    }

    /**
     * @test
     */
    public function user_can_delete_empty_basket()
    {
        $this->browse(
            function (Browser $browser) {
                // проверяем, что корзинка есть
                $baskets = $browser->elements('.account-baskets__item');
                $this->assertEquals(1, count($baskets));

                // удадяем корзинку
                $browser->press('@basket-show-detail-btn')
                    ->press('@basket-delete-btn');

                $browser->pause(1000);

                // проверяем, что корзинка удалилась
                $baskets = $browser->elements('.account-baskets__item');
                $this->assertEquals(0, count($baskets));
            }
        );
    }

    /**
     * @test
     */
    public function user_cannot_delete_basket_with_active_orders()
    {
        $this->browse(
            function (Browser $browser) {
                // проверяем, что корзинка есть
                $baskets = $browser->elements('.account-baskets__item');
                $this->assertEquals(1, count($baskets));

                $order = \App\Models\Order::create(
                    [
                        'currency_1_code'    => 'BTC',
                        'currency_2_code'    => 'USD',
                        'basket_id'          => 1,
                        'exchange_market_id' => 1,
                        'amount'             => 1,
                        'gained_amount'      => 1,
                        'price'              => 1,
                        'action'             => SELL_ACTION_CODE,
                        'id_at_exm'          => 1,
                    ]
                );

                // удадяем корзинку
                $browser->press('@basket-show-detail-btn')
                    ->press('@basket-delete-btn');

                // проверяем, что корзинка не удалилась
                $baskets = $browser->elements('.account-baskets__item');
                $this->assertEquals(1, count($baskets));

                // и что показалась ошибка
                $browser->assertSee('Нельзя удалить корзинку, у которой есть активные ордера');
            }
        );
    }

    /**
     * @test
     */
    public function user_can_archive_basket_with_done_orders()
    {
        $this->browse(
            function (Browser $browser) {
                // проверяем, что корзинка есть
                $baskets = $browser->elements('.account-baskets__item');
                $this->assertEquals(1, count($baskets));

                // создаём ордер
                $order = \App\Models\Order::create(
                    [
                        'currency_1_code'    => 'BTC',
                        'currency_2_code'    => 'USD',
                        'basket_id'          => 1,
                        'exchange_market_id' => 1,
                        'amount'             => 1,
                        'gained_amount'      => 1,
                        'price'              => 1,
                        'action'             => SELL_ACTION_CODE,
                        'id_at_exm'          => 1,
                        'done'               => true,
                    ]
                );

                // удадяем корзинку
                $browser->press('@basket-show-detail-btn')
                    ->press('@basket-delete-btn');

                $browser->pause(1000);

                // проверяем, что корзинка удалилась
                $baskets = $browser->elements('.account-baskets__item');
                $this->assertEquals(0, count($baskets));

                // проверяем в бд, что корзинка стала архивированной
                $basket = \App\Models\Basket::first();
                $this->assertEquals(true, $basket->archive);
            }
        );
    }
}