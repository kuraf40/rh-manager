<?php

namespace App\Http\Controllers;

use App\Models\DisciplinaryAction;
use App\Models\Employee;
use App\Models\RegisteredHour;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $startDate = CarbonImmutable::now()->startOfMonth()->subMonths(5);
        $endDate = CarbonImmutable::now()->endOfMonth();
        $monthlyHours = collect(range(0, 5))
            ->mapWithKeys(fn (int $monthsAgo): array => [
                $startDate->addMonths($monthsAgo)->format('Y-m') => 0.0,
            ]);

        RegisteredHour::query()
            ->whereBetween('date_enreg', [$startDate->toDateString(), $endDate->toDateString()])
            ->get(['date_enreg', 'total_heures'])
            ->each(function (RegisteredHour $hour) use ($monthlyHours): void {
                $month = CarbonImmutable::parse($hour->date_enreg)->format('Y-m');
                $monthlyHours[$month] += (float) $hour->total_heures;
            });

        $typeLabels = [
            'demande_explication' => "Demande d'explication",
            'avertissement' => 'Avertissement',
            'blame' => 'Blâme',
            'mise_a_pied' => 'Mise à pied',
        ];
        $actionsByType = DisciplinaryAction::query()
            ->select('type')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');
        $sanctionsByType = collect($typeLabels)
            ->map(fn (string $label, string $type): array => [
                'type' => $type,
                'label' => $label,
                'total' => (int) ($actionsByType[$type] ?? 0),
            ])
            ->values();

        return Inertia::render('Dashboard', [
            'totalEmployees' => Employee::where('actif', true)->count(),
            'totalHours' => RegisteredHour::sum('total_heures'),
            'totalSanctions' => $sanctionsByType->sum('total'),
            'monthlyHours' => $monthlyHours
                ->map(fn (float $totalHours, string $month): array => [
                    'month' => $month,
                    'totalHours' => round($totalHours, 2),
                ])
                ->values(),
            'sanctionsByType' => $sanctionsByType,
        ]);
    }
}
