<?php

namespace App\Services;

use App\Models\RegisteredHour;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class OvertimeRegistrationService
{
    private const WEEKLY_LIMIT = 5;

    /**
     * @return list<array{
     *     date_enreg: string,
     *     heure_debut: string,
     *     heure_fin: string,
     *     vacation: string,
     *     type_jour: string,
     *     total_heures: float,
     *     h_jour: float,
     *     h_nuit: float,
     *     h_ferie_jour: float,
     *     h_ferie_nuit: float,
     *     mois_reference: string,
     *     semaine_reference: int
     * }>
     */
    public function buildSegments(
        string $date,
        string $startTime,
        string $endTime,
        string $vacation,
        string $dayType,
    ): array {
        $start = CarbonImmutable::parse($date.' '.$startTime);
        $end = CarbonImmutable::parse($date.' '.$endTime);

        if ($end->lessThanOrEqualTo($start)) {
            $end = $end->addDay();
        }

        $segments = [];
        $cursor = $start;

        while ($cursor->lessThan($end)) {
            $segmentDate = $cursor->startOfDay();
            $nextDate = $segmentDate->addDay();
            $segmentEnd = $end->lessThan($nextDate) ? $end : $nextDate;

            if (! $this->isWithinAuthorizedWindow($cursor, $segmentEnd, $segmentDate, $vacation)) {
                throw ValidationException::withMessages([
                    'heure_debut' => 'La plage doit être entièrement comprise dans une pause ou en dehors de la vacation choisie.',
                ]);
            }

            $totalMinutes = intdiv($segmentEnd->getTimestamp() - $cursor->getTimestamp(), 60);
            $dayMinutes = $this->dayMinutes($cursor, $segmentEnd, $segmentDate, $vacation);
            $nightMinutes = $totalMinutes - $dayMinutes;
            $isHoliday = $dayType === 'ferie';
            $reference = $this->referenceForDate($segmentDate);

            $segments[] = [
                'date_enreg' => $segmentDate->toDateString(),
                'heure_debut' => $cursor->format('H:i'),
                'heure_fin' => $segmentEnd->format('H:i'),
                'vacation' => $vacation,
                'type_jour' => $dayType,
                'total_heures' => round($totalMinutes / 60, 2),
                'h_jour' => $isHoliday ? 0 : round($dayMinutes / 60, 2),
                'h_nuit' => $isHoliday ? 0 : round($nightMinutes / 60, 2),
                'h_ferie_jour' => $isHoliday ? round($dayMinutes / 60, 2) : 0,
                'h_ferie_nuit' => $isHoliday ? round($nightMinutes / 60, 2) : 0,
                'mois_reference' => $reference['month'],
                'semaine_reference' => $reference['week'],
            ];

            $cursor = $segmentEnd;
        }

        return $segments;
    }

    /**
     * @param  list<array<string, int|float|string>>  $segments
     */
    public function weeklyLimitWarning(int $employeeId, array $segments): ?string
    {
        $proposedHours = [];

        foreach ($segments as $segment) {
            $key = $segment['mois_reference'].'-'.$segment['semaine_reference'];
            $proposedHours[$key] ??= [
                'month' => $segment['mois_reference'],
                'week' => $segment['semaine_reference'],
                'hours' => 0.0,
            ];
            $proposedHours[$key]['hours'] += (float) $segment['total_heures'];
        }

        foreach ($proposedHours as $period) {
            $periodStart = CarbonImmutable::parse($period['month'].'-20');
            $weekStart = $periodStart->addDays(((int) $period['week'] - 1) * 7);
            $periodEnd = $periodStart->addMonthNoOverflow();
            $weekEndExclusive = $weekStart->addDays(7);

            if ($weekEndExclusive->greaterThan($periodEnd)) {
                $weekEndExclusive = $periodEnd;
            }

            $recordedHours = (float) RegisteredHour::query()
                ->where('employee_id', $employeeId)
                ->whereBetween('date_enreg', [
                    $weekStart->toDateString(),
                    $weekEndExclusive->subDay()->toDateString(),
                ])
                ->sum('total_heures');
            $totalAfterEntry = $recordedHours + $period['hours'];

            if ($totalAfterEntry > self::WEEKLY_LIMIT) {
                return sprintf(
                    'Cette saisie porterait le total à %s h pour la semaine %d du mois de référence %s (limite : 5 h). Confirmer l’enregistrement ?',
                    number_format($totalAfterEntry, 2, ',', ' '),
                    $period['week'],
                    $period['month'],
                );
            }
        }

        return null;
    }

    private function isWithinAuthorizedWindow(
        CarbonImmutable $start,
        CarbonImmutable $end,
        CarbonImmutable $date,
        string $vacation,
    ): bool {
        foreach ($this->authorizedWindows($date, $vacation) as [$windowStartTime, $windowEndTime]) {
            $windowStart = CarbonImmutable::parse($date->toDateString().' '.$windowStartTime);
            $windowEnd = $windowEndTime === '24:00'
                ? $date->addDay()
                : CarbonImmutable::parse($date->toDateString().' '.$windowEndTime);

            if ($start->greaterThanOrEqualTo($windowStart) && $end->lessThanOrEqualTo($windowEnd)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{string, string}>
     */
    private function authorizedWindows(CarbonImmutable $date, string $vacation): array
    {
        $weekday = $date->dayOfWeekIso;

        if ($vacation === '07:00-15:00') {
            if ($weekday >= 1 && $weekday <= 4) {
                return [['00:00', '07:00'], ['10:00', '11:00'], ['15:00', '24:00']];
            }

            if ($weekday === 5 || $weekday === 6) {
                return [['00:00', '07:00'], ['10:00', '11:00'], ['13:00', '14:00'], ['15:00', '24:00']];
            }
        }

        if ($vacation === '15:00-23:00') {
            if ($weekday >= 1 && $weekday <= 4) {
                return [['00:00', '15:00'], ['19:30', '21:00'], ['23:00', '24:00']];
            }

            if ($weekday === 5 || $weekday === 6) {
                return [['00:00', '15:00'], ['20:00', '21:00'], ['23:00', '24:00']];
            }
        }

        if ($vacation === '23:00-07:00' && $weekday >= 1 && $weekday <= 5) {
            return [['06:00', '07:00'], ['07:00', '23:00']];
        }

        return [];
    }

    private function dayMinutes(
        CarbonImmutable $start,
        CarbonImmutable $end,
        CarbonImmutable $date,
        string $vacation,
    ): int {
        if ($vacation === '23:00-07:00') {
            return 0;
        }

        $dayStart = CarbonImmutable::parse($date->toDateString().' 07:00');
        $dayEndTime = $vacation === '07:00-15:00' ? '21:00' : '23:00';
        $dayEnd = CarbonImmutable::parse($date->toDateString().' '.$dayEndTime);
        $overlapStart = $start->greaterThan($dayStart) ? $start : $dayStart;
        $overlapEnd = $end->lessThan($dayEnd) ? $end : $dayEnd;

        if ($overlapEnd->lessThanOrEqualTo($overlapStart)) {
            return 0;
        }

        return intdiv($overlapEnd->getTimestamp() - $overlapStart->getTimestamp(), 60);
    }

    /**
     * @return array{month: string, week: int}
     */
    private function referenceForDate(CarbonImmutable $date): array
    {
        $month = $date->day < 20 ? $date->subMonthNoOverflow() : $date;
        $monthReference = $month->format('Y-m');
        $periodStart = CarbonImmutable::parse($monthReference.'-20');

        return [
            'month' => $monthReference,
            'week' => (int) floor($periodStart->diffInDays($date) / 7) + 1,
        ];
    }
}
