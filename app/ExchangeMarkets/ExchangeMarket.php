<?php

namespace App\ExchangeMarkets;

use App\Trading\Order;

abstract class ExchangeMarket
{
    public $idInDB;

    /**
     * @param string $public_key
     * @param string $secret_key
     *
     * @return bool
     */
    abstract function connect($public_key, $secret_key): bool;

    abstract public function getCode(): string;

    // todo обязать потомков возвращать массив определённой структуры
    abstract function getBalances($public_key, $secret_key);

    // todo обязать потомков возвращать массив определённой структуры
    abstract function getCurrenciesRates();

    /**
     * Ставит ордер на бирже
     * В случае успеха возвращает id
     *
     * @param Order $order
     * @param $public_key
     * @param $secret_key
     *
     * @return string|bool
     */
    abstract function placeOrder(Order $order, $public_key, $secret_key);

    /**
     * В случае успеха возвращает массив с полем gained_amount
     *
     * @param $exmOrderID
     * @param $currency1Amount double Используется для проверки, что ордер совершён
     * @param $public_key
     * @param $secret_key
     *
     * @return mixed
     */
    abstract function getDoneOrderInfo($exmOrderID, $currency1Amount, $public_key, $secret_key);

    /**
     * Получение списка валют и их лимитов
     *
     * @param integer $idInDB
     *
     * @return mixed
     */
    abstract function updatePairsAndSettings($idInDB = null);

    public function getIDinDB()
    {
        if (intval($this->idInDB) >= 1) {
            return intval($this->idInDB);
        }

        $exm = \App\Models\ExchangeMarket::where('code', $this->getCode())->first(); // todo кеширование

        // сразу же присваиваем объекту в целяз кеширования
        $this->idInDB = $exm->id;

        return $exm->id;
    }

    /**
     * Получение стаканов и объёмов торгов
     *
     * @return mixed
     */
    abstract function getOrderBook();

    protected function floorToMinute($timestamp)
    {
        return floor($timestamp / SECONDS_IN_MINUTE) * SECONDS_IN_MINUTE;
    }
}