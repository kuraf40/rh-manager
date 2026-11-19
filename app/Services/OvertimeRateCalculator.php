<?php

namespace App\Services;

class OvertimeRateCalculator
{
    /**
     * @return array{hs_12: float, hs_35: float, hs_50: float, hs_100: float}
     */
    public function calculate(
        float $dayHours,
        float $nightHours,
        float $holidayDayHours,
        float $holidayNightHours,
    ): array {
        return [
            'hs_12' => round(min($dayHours, 8), 2),
            'hs_35' => round(max(0, $dayHours - 8), 2),
            'hs_50' => round($holidayDayHours + $nightHours, 2),
            'hs_100' => round($holidayNightHours, 2),
        ];
    }
}
