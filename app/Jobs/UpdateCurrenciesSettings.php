<?php

namespace App\Jobs;

use App\ExchangeMarkets\ExchangeMarketFabric;
use App\Models\ExchangeMarket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateCurrenciesSettings implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach (ExchangeMarket::all() as $exmInDB) {
            $exchangeMarket = ExchangeMarketFabric::get($exmInDB->code);

            $exchangeMarket->updatePairsAndSettings($exmInDB->id);
        }
    }
}
