<?php

namespace App\Models\Metrics;

use App\Helpers\DateTimeHelper;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Database\Eloquent\Model;

class Metrics extends Model{

    protected $table = 'metrics';
    public $timestamps = false;
    protected $fillable = [
        'sort',
        'code',
        'name',
        'description',
        'type',
        'public',
        'ideal_value',
    ];

    public static function calculateMetrics(){
        // посчитаем все метрики-запросы
        $queryMetrics = static::where('type', 'query')->get();
        foreach ($queryMetrics as $metric){
            switch($metric->code){ // не храним их запросы в бд

            }

            if (isset($value)){
                $metric->saveValue($value);
            }
        }
    }

    public function saveValue($value){
        $timestampForValues = static::getTimestampForValues();

        $currentValue = $this->values()->where('timestamp', $timestampForValues)->first();

        if ($currentValue){
            $counter = $currentValue->counter;
        } else{
            $counter = 0;
        }

        if ($this->type == 'query'){
            if (!$currentValue){
                return $this->values()->create(
                    [
                        'timestamp' => $timestampForValues,
                        'value'     => $value,
                        'counter'   => $counter + 1,
                    ]
                );
            } else{
                return $currentValue->update(
                    [
                        'value'   => $value,
                        'counter' => $counter + 1,
                    ]
                );
            }
        }
        if ($this->type == 'average'){
            if (!$currentValue){
                return $this->values()->create(
                    [
                        'timestamp' => $timestampForValues,
                        'value'     => $value,
                        'counter'   => $counter + 1,
                    ]
                );
            } else{
                return $currentValue->update(
                    [
                        'value'   => static::changeAverageValueByNewValue($currentValue->value, $counter, $value),
                        'counter' => $counter + 1,
                    ]
                );
            }
        }
        if ($this->type == 'counter'){
            if (!$currentValue){
                return $this->values()->create(
                    [
                        'timestamp' => $timestampForValues,
                        'value'     => $value,
                        'counter'   => $counter + 1,
                    ]
                );
            } else{
                return $currentValue->update(
                    [
                        'value'   => $currentValue->value + $value,
                        'counter' => $counter + 1,
                    ]
                );
            }
        }
    }

    // усреднить среднее значение к новому значению
    public static function changeAverageValueByNewValue($oldValue, $counter, $newValue){
        return $oldValue - (($oldValue - $newValue) / ($counter + 1));
    }

    public static function getTimestampForValues(){
        return DateTimeHelper::getLastMidnight()->timestamp;
    }

    public static function log($code, $value){
        $metric = static::where('code', $code)->first();

        if ($metric){
            return $metric->saveValue($value);
        }

        return false;
    }

    // todo рефакторинг
    public function getLastMonthValues(){
        $begin = new DateTime((new DateTime())->format('Y-m-d'));
        $begin = $begin->modify('-1 month');
        $end   = new DateTime((new DateTime())->format('Y-m-d'));
        $end   = $end->modify('+1 day');

        $interval = DateInterval::createFromDateString('1 day');
        $period   = new DatePeriod($begin, $interval, $end);

        $values = [];
        foreach ($period as $dt){
            $value = '';

            $metricValue = $this->values()->where('timestamp', $dt->format('U'))->first();
            if ($metricValue){
                $value = $metricValue->value;
            }

            $values[$dt->format('m.d.Y')] = $value;
        }

        return $values;
    }

    // свойства вне бд
    function getLastValueAttribute(){
        $value = $this->values()->orderBy('timestamp', 'desc')->first();

        return $value;
    }
    // .свойства вне бд

    // связи с другими моделями
    public function values(){
        return $this->hasMany('App\Models\Metrics\MetricsValue');
    }
}
