<?php

namespace App\CurrencyPairsMetrics;

use App\Models\CurrencyPairTrend;
use Illuminate\Support\Facades\Cache;

class Trend implements Calculatable
{
    public static $cacheCodeForExtremumsPair = 'last_analyzed_extremums_pair';

    public static function calculate($currencyPairID, $currencyPairCode, $currentTimestamp = '')
    {
        // получаем экстремумы для работы
        $maximums = Extremum::getMaximumsForPeriod($currencyPairCode, HOURS_PER_DAY * MINUTES_IN_HOUR);
        if (count($maximums) < 2) { // нужно как минимум 2 точки, чтобы провести линию
            return false;
        }
        $minimums = Extremum::getMinimumsForPeriod($currencyPairCode, HOURS_PER_DAY * MINUTES_IN_HOUR);
        if (count($minimums) < 2) { // нужно как минимум 2 точки, чтобы провести линию
            return false;
        }
        $lastMaximum = $maximums[count($maximums) - 1];
        $lastMinimum = $minimums[count($minimums) - 1];

        // экстремумы появляются не на каждом тике, поэтому нам нужно проверять, что такую пару мы ещё не проверяли
        if (static::extremumsPairWasAnalysed($currencyPairCode, $lastMaximum, $lastMinimum)) {
            return false;
        }

        // защита от повторения проверки
        static::rememberExtremumsPairWasAnalysed($currencyPairCode, $lastMaximum, $lastMinimum);

        // собираем все линии между экстремумами, которые можно протянуть к новому максимуму
        $topLines = static::getAllInterruptedLinesToRightPoint($maximums);
        $bottomLines = static::getAllInterruptedLinesToRightPoint($minimums);

        // собираем все линии, которые коррелируют между собой. Только такие создают тренд
        $linePairs = static::pairTopAndBottomLinesByCorrelation($topLines, $bottomLines);

        // фильтруюм пары по пересечению во временных промежутках, чтобы оставить только те, что создают тренд
        $linePairs = static::filterLinePairsByTimeIntersection($linePairs);

        // сохраняем тренды
        foreach ($linePairs as $linePair) {
            $type = static::determineTrendType(
                $linePair['top_line']['x1'],
                $linePair['top_line']['x2'],
                $linePair['bottom_line']['x1'],
                $linePair['bottom_line']['x2']
            );
            if (!$type) { // не сохраняем тренды без явного типа, так как они не принесут пользы
                continue;
            }

            CurrencyPairTrend::create(
                [
                    'currency_pair_id' => $currencyPairID,
                    'type'             => $type,
                    'lt_x'             => $linePair['top_line']['x1'],
                    'lt_y'             => $linePair['top_line']['y1'],
                    'rt_x'             => $linePair['top_line']['x2'],
                    'rt_y'             => $linePair['top_line']['y2'],
                    'lb_x'             => $linePair['bottom_line']['x1'],
                    'lb_y'             => $linePair['bottom_line']['y1'],
                    'rb_x'             => $linePair['bottom_line']['x2'],
                    'rb_y'             => $linePair['bottom_line']['y2'],
                ]
            );
        }
    }

    public static function determineTrendType($tx1, $tx2, $bx1, $bx2)
    {
        if ($tx1 < $tx2 && $bx1 < $bx2) {
            return 'up';
        }
        if ($tx1 > $tx2 && $bx1 > $bx2) {
            return 'down';
        }

        return false;
    }

    protected static function filterLinePairsByTimeIntersection($linePairs)
    {
        $filteredPair = [];

        foreach ($linePairs as $linePair) {
            $topLine = $linePair['top_line'];
            $bottomLine = $linePair['bottom_line'];
            $topLineStart = $topLine['x1'];
            $topLineEnd = $topLine['x2'];
            $bottomLineStart = $bottomLine['x1'];
            $bottomLineEnd = $bottomLine['x2'];

            if ($topLineStart > $bottomLineEnd) { // если верхняя линия начинается позже конца нижней
                continue;
            }
            if ($topLineEnd < $bottomLineStart) { // если верхняя линия кончается раньше начала нижней
                continue;
            }
            if ($bottomLineStart > $topLineEnd) { // если нижняя линия начинается позже конца верхней
                continue;
            }
            if ($bottomLineEnd < $topLineStart) { // если нижняя линия кончается раньше начала верхней
                continue;
            }

            $filteredPair[] = $linePair;
        }

        return $filteredPair;
    }

    protected static function extremumsPairWasAnalysed($currencyPairCode, $maximum, $minimum)
    {
        return Cache::get($currencyPairCode.'.'.static::$cacheCodeForExtremumsPair)
            == static::getExtremumsPairStringForCache($maximum, $minimum);
    }

    protected static function rememberExtremumsPairWasAnalysed($currencyPairCode, $maximum, $minimum)
    {
        Cache::put(
            $currencyPairCode.'.'.static::$cacheCodeForExtremumsPair,
            static::getExtremumsPairStringForCache($maximum, $minimum),
            HOURS_PER_DAY * MINUTES_IN_HOUR // за сутки, так как в любом случае нужно обновить тренды на следующий день
        );
    }

    protected static function getExtremumsPairStringForCache($maximum, $minimum)
    {
        return $maximum['timestamp'].'#'.$minimum['timestamp'];
    }

    // todo поскольку тут мне нужны только соотношения a к b, можно не возвращать из getAllInterruptedLinesToRightPoint c, а также a и b по отдельности
    public static function pairTopAndBottomLinesByCorrelation($topLines, $bottomLines)
    {
        // если корреляция между этими коэффициентами - считаем, что линии параллельны
        $correlationThresholdBottom = 0.9;
        $correlationThresholdTop = 1.1;
        $pairs = [];

        foreach ($topLines as $topLine) {
            foreach ($bottomLines as $bottomLine) {
                $topLineXMultiplier = $topLine['a'] / $topLine['b'];
                $bottomLineXMultiplier = $bottomLine['a'] / $bottomLine['b'];
                $max = max($topLineXMultiplier, $bottomLineXMultiplier);
                $min = min($topLineXMultiplier, $bottomLineXMultiplier);
                $corrCoef = $min ? $max / $min : 0; // обходим проблему деления на ноль

                if ($correlationThresholdBottom <= $corrCoef && $corrCoef <= $correlationThresholdTop) {
                    $pairs[] = [
                        'corr_coef'   => $corrCoef,
                        'top_line'    => $topLine,
                        'bottom_line' => $bottomLine,
                    ];
                }
            }
        }

        return $pairs;
    }

    public static function getAllInterruptedLinesToRightPoint($points, $fromTop = true, $permissiblePercent = 1)
    {
        if (!count($points)) {
            return [];
        }

        $pointIndexesToSkip = []; // точки, которые лежат на более длинных линиях. Например, 2 на линии 1, 2, 3
        $lastIndex = count($points) - 1;
        $rightestPoint = $points[count($points) - 1];
        $lines = [];
        // идём по всем точкам, кроме самой правой
        for ($testedIndex = 0; $testedIndex < $lastIndex; $testedIndex++) {
            if (in_array($testedIndex, $pointIndexesToSkip)) {
                continue;
            }

            $testedPoint = $points[$testedIndex];
            // коэффициенты для формулы прямой между проверяемой точкой и самой правой
            $a = $testedPoint['value'] - $rightestPoint['value'];
            $b = $rightestPoint['timestamp'] - $testedPoint['timestamp'];
            $c = $testedPoint['timestamp'] * $rightestPoint['value'] - $rightestPoint['timestamp'] * $testedPoint['value'];

            $failed = false;
            for ($j = $testedIndex; $j < $lastIndex; $j++) {
                $currentPoint = $points[$j];
                $lineValueAtThisPoint = (-$c - $a * $currentPoint['timestamp']) / $b;

                if ($fromTop) {
                    if ($currentPoint['value'] > $lineValueAtThisPoint * (1 + ($permissiblePercent / 100))) {
                        $failed = true;
                        break;
                    }
                } else {
                    if ($currentPoint['value'] < $lineValueAtThisPoint * (1 - ($permissiblePercent / 100))) {
                        $failed = true;
                        break;
                    }
                }

                // если точка лежит на линии, то не будем потом пытаться провести линию такую точку
                if (($currentPoint['value'] <= $lineValueAtThisPoint * (1 + ($permissiblePercent / 100))) && ($currentPoint['value'] >= $lineValueAtThisPoint * (1 - ($permissiblePercent / 100)))) {
                    $pointIndexesToSkip[] = $j;
                }
            }

            // если линия не прерывается
            if (!$failed) {
                $lines[] = [
                    'x1' => $testedPoint['timestamp'],
                    'y1' => $testedPoint['value'],
                    'x2' => $rightestPoint['timestamp'],
                    'y2' => $rightestPoint['value'],
                    'a'  => $a,
                    'b'  => $b,
                    'c'  => $c,
                ];
            }
        }

        return $lines;
    }

    public static function clearOlderThan($notLateThanTimestamp)
    {
        CurrencyPairTrend::where('rt_x', '<', $notLateThanTimestamp)
            ->where('rb_x', '<', $notLateThanTimestamp)
            ->delete();
    }
}