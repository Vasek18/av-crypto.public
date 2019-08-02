<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateTimeHelper{

    /**
     * @return Carbon
     */
    public static function getTodayMidnight(){
        return Carbon::tomorrow();
    }

    /**
     * @return Carbon
     */
    public static function getLastMidnight(){
        return Carbon::today();
    }

    /**
     * @param Carbon $date
     * @return Carbon
     */
    // обнулить минуты и секунды
    public static function clearMinutesAndSeconds(Carbon $date){
        return Carbon::parse($date->format('d.m.Y H:00:00'));
    }

    /**
     * @param Carbon $date
     * @return Carbon
     */
    // обнулить до полуночи
    public static function clearHours(Carbon $date){
        return Carbon::parse($date->format('d.m.Y 0:00:00'));
    }

    public static function getFormat(){
        return 'Y-m-d H:i:s';
    }
}
