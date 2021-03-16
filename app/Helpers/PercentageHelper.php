<?php

namespace App\Helpers;

class PercentageHelper
{
    public static function getPercentage($initialDuration, $durationLeft)
    {
        if ($initialDuration > 0) {
            return round($durationLeft * ($initialDuration / 100), 2);
        } else {
            return 0;
        }
    }
}
