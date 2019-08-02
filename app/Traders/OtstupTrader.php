<?php

namespace App\Traders;

use App\Trading\CurrencyPairRate;
use Illuminate\Support\Facades\Redis;

class OtstupTrader extends Trader
{
    public $sellLastMin        = false;
    public $sellLastMax        = false;
    public $buyLastMin         = false;
    public $buyLastMax         = false;
    public $last_move          = false;
    public $otstupPercentStart = 0.5;
    public $otstupPercentEnd   = 1;

    public function setSellLastMax($max)
    {
        if ($this->test) {
            $this->sellLastMax = $max;
        } else {
            Redis::set('otstup_trader.sell.last_max.'.$this->currencyPairCode, $max);
        }
    }

    public function setBuyLastMax($max)
    {
        if ($this->test) {
            $this->buyLastMax = $max;
        } else {
            Redis::set('otstup_trader.buy.last_max.'.$this->currencyPairCode, $max);
        }
    }

    public function getSellLastMax()
    {
        if ($this->test) {
            return $this->sellLastMax;
        } else {
            $max = Redis::get('otstup_trader.sell.last_max.'.$this->currencyPairCode);

            return $max ?? false;
        }
    }

    public function getBuyLastMax()
    {
        if ($this->test) {
            return $this->buyLastMax;
        } else {
            $max = Redis::get('otstup_trader.buy.last_max.'.$this->currencyPairCode);

            return $max ?? false;
        }
    }

    public function setSellLastMin($min)
    {
        if ($this->test) {
            $this->sellLastMin = $min;
        } else {
            Redis::set('otstup_trader.sell.last_min.'.$this->currencyPairCode, $min);
        }
    }

    public function setBuyLastMin($min)
    {
        if ($this->test) {
            $this->buyLastMin = $min;
        } else {
            Redis::set('otstup_trader.buy.last_min.'.$this->currencyPairCode, $min);
        }
    }

    public function getSellLastMin()
    {
        if ($this->test) {
            return $this->sellLastMin;
        } else {
            $min = Redis::get('otstup_trader.sell.last_min.'.$this->currencyPairCode);

            return $min ?? false;
        }
    }

    public function getBuyLastMin()
    {
        if ($this->test) {
            return $this->buyLastMin;
        } else {
            $min = Redis::get('otstup_trader.buy.last_min.'.$this->currencyPairCode);

            return $min ?? false;
        }
    }

    public function clearSellLastMax()
    {
        if ($this->test) {
            $this->sellLastMax = false;
        } else {
            Redis::del('otstup_trader.sell.last_max.'.$this->currencyPairCode);
        }
    }

    public function clearBuyLastMax()
    {
        if ($this->test) {
            $this->buyLastMax = false;
        } else {
            Redis::del('otstup_trader.buy.last_max.'.$this->currencyPairCode);
        }
    }

    public function clearSellLastMin()
    {
        if ($this->test) {
            $this->sellLastMin = false;
        } else {
            Redis::del('otstup_trader.sell.last_min.'.$this->currencyPairCode);
        }
    }

    public function clearBuyLastMin()
    {
        if ($this->test) {
            $this->buyLastMin = false;
        } else {
            Redis::del('otstup_trader.buy.last_min.'.$this->currencyPairCode);
        }
    }

    public function getOtstupPercentStart()
    {
        return $this->otstupPercentStart;
    }

    public function getOtstupPercentEnd()
    {
        return $this->otstupPercentEnd;
    }

    public function isThisIsPriceOfSell($price, $lastMin, $lastMax)
    {
        if (!$lastMin || !$lastMax) { // если данных так мало, что у нас нет пределов с обоих сторон, то мы не можем принять решение
            return false;
        }
        $otstupFromMax = ($lastMax - $price) / ($lastMax / 100);
        $otstupFromMin = ($price - $lastMin) / ($lastMin / 100);

        if ($this->getOtstupPercentStart() <= $otstupFromMax && $otstupFromMax <= $this->getOtstupPercentEnd(
            )) { // если цена внутри диапазона отступов
            if ($otstupFromMin > $otstupFromMax * 2) { // если до этого был рост минимум в два раза больший, чем падение сейчас
                return true;
            }
        }

        return false;
    }

    public function isThisIsPriceOfBuy($price, $lastMin, $lastMax)
    {
        if (!$lastMin || !$lastMax) { // если данных так мало, что у нас нет пределов с обоих сторон, то мы не можем принять решение
            return false;
        }
        $otstupFromMax = ($lastMax - $price) / ($lastMax / 100);
        $otstupFromMin = ($price - $lastMin) / ($lastMin / 100);

        if (
            $this->getOtstupPercentStart() <= $otstupFromMin
            &&
            $otstupFromMin <= $this->getOtstupPercentEnd()
        ) { // если цена внутри диапазона отступов
            if ($otstupFromMin * 2 < $otstupFromMax) { // если до этого было падение минимум в два раза большее, чем рост сейчас // todo это условие походу неправильное, так как мы высчитываем падение через текущую цену, а не минимум, соответственно получается, что условие срабатывает, когда падение было >3 больше, чем рост сейчас
                return true;
            }
        }

        return false;
    }

    public function getDecision(CurrencyPairRate $rate)
    {
        $sellLastMin = $this->getSellLastMin();
        $sellLastMax = $this->getSellLastMax();
        $buyLastMin = $this->getBuyLastMin();
        $buyLastMax = $this->getBuyLastMax();

        // устанавливаем максимумы и минимумы
        if (!$sellLastMax || $rate->sell_price >= $sellLastMax) {
            $this->setSellLastMax($rate->sell_price);
        }
        if (!$sellLastMin || $rate->sell_price <= $sellLastMin) {
            $this->setSellLastMin($rate->sell_price);
        }
        if (!$buyLastMax || $rate->buy_price >= $buyLastMax) {
            $this->setBuyLastMax($rate->buy_price);
        }
        if (!$buyLastMin || $rate->buy_price <= $buyLastMin) {
            $this->setBuyLastMin($rate->buy_price);
        }

        // если это тот диапазон, где мы принимаем решения, то возвращаем его и очищаем противоположный предел
        if ($this->isThisIsPriceOfBuy($rate->buy_price, $buyLastMin, $buyLastMax)) {
            $this->clearBuyLastMax();
            $this->clearSellLastMax();

            return new TraderDecision($rate->buy_price, $rate->timestamp, BUY_ACTION_CODE, 'OtstupTrader');
        }
        if ($this->isThisIsPriceOfSell($rate->sell_price, $sellLastMin, $sellLastMax)) {
            $this->clearSellLastMin();
            $this->clearBuyLastMin();

            return new TraderDecision($rate->sell_price, $rate->timestamp, SELL_ACTION_CODE, 'OtstupTrader');
        }

        return false;
    }
}