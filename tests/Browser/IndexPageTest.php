<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class IndexPageTest extends DuskTestCase
{

    use DatabaseMigrations;

    function setUp()
    {
        parent::setUp();
    }

    /**
     * Важный тест, так как иногда при разработке я ломаю главную страницу
     *
     * @test
     */
    public function user_can_visit_index_page()
    {
        $this->browse(
            function (Browser $browser) {
                $browser->visit('/')
                    ->assertSee('Вася');
            }
        );
    }
}
