<?php

namespace App\Events;

use App\Trading\CurrencyPairRate;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CurrencyPairRateChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $currencyPairID;
    public $currencyPairCode;
    public $rate;

    /**
     * @param integer $currencyPairID
     * @param string $currencyPairCode
     * @param CurrencyPairRate $rate
     */
    public function __construct(
        $currencyPairID,
        $currencyPairCode,
        CurrencyPairRate $rate
    ) {
        $this->currencyPairID = $currencyPairID;
        $this->currencyPairCode = $currencyPairCode;
        $this->rate = $rate;
    }
}
