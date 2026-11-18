<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\RegisteredHour;
use Carbon\CarbonImmutable;
use Carbon\WeekDay;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredHourController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Hours/Index', [
            'employees' => Employee::where('actif', true)->orderBy('nom')->get(['id', 'matricule', 'nom', 'prenom']),
            'recentHours' => RegisteredHour::with('employee')
                ->latest()
                ->take(10)
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date_enreg' => 'required|date',
            'heure_debut' => ['required', 'date_format:H:i'],
            'heure_fin' => ['required', 'date_format:H:i'],
            'pause_minutes' => ['nullable', 'integer', 'min:0', 'max:720'],
            'h_jour' => ['required', 'numeric', 'min:0', 'max:24'],
            'h_nuit' => ['required', 'numeric', 'min:0', 'max:24'],
            'h_ferie_jour' => ['required', 'numeric', 'min:0', 'max:24'],
            'h_ferie_nuit' => ['required', 'numeric', 'min:0', 'max:24'],
        ]);

        $debut = CarbonImmutable::parse($validated['date_enreg'].' '.$validated['heure_debut']);
        $fin = CarbonImmutable::parse($validated['date_enreg'].' '.$validated['heure_fin']);

        if ($fin->lessThanOrEqualTo($debut)) {
            $fin = $fin->addDay();
        }

        $pauseMinutes = (int) ($validated['pause_minutes'] ?? 0);
        $totalMinutes = $debut->diffInMinutes($fin) - $pauseMinutes;
        $totalHeures = round($totalMinutes / 60, 2);
        $ventilation = round(
            (float) $validated['h_jour'] +
            (float) $validated['h_nuit'] +
            (float) $validated['h_ferie_jour'] +
            (float) $validated['h_ferie_nuit'],
            2,
        );

        if ($totalMinutes <= 0) {
            throw ValidationException::withMessages(['pause_minutes' => 'La pause doit être inférieure à la durée du poste.']);
        }

        if (abs($ventilation - $totalHeures) > 0.01) {
            throw ValidationException::withMessages(['h_jour' => 'La somme des catégories doit correspondre à la durée nette du poste.']);
        }

        $dateEnreg = CarbonImmutable::parse($validated['date_enreg']);

        RegisteredHour::create([
            'employee_id' => $validated['employee_id'],
            'date_enreg' => $validated['date_enreg'],
            'heure_debut' => $validated['heure_debut'],
            'heure_fin' => $validated['heure_fin'],
            'total_heures' => $totalHeures,
            'h_jour' => $validated['h_jour'],
            'h_nuit' => $validated['h_nuit'],
            'h_ferie_jour' => $validated['h_ferie_jour'],
            'h_ferie_nuit' => $validated['h_ferie_nuit'],
            'mois_reference' => $dateEnreg->format('Y-m'),
            'semaine_reference' => $dateEnreg->weekOfYear,
        ]);

        return back()->with('message', 'Heures enregistrées avec succès.');
    }

    public function summary(Request $request): Response
    {
        $validated = $request->validate([
            'period' => ['sometimes', Rule::in(['week', 'month'])],
            'date' => ['sometimes', 'date'],
        ]);
        $period = $validated['period'] ?? 'month';
        $date = CarbonImmutable::parse($validated['date'] ?? now()->toDateString());

        if ($period === 'week') {
            $startDate = $date->startOfWeek(WeekDay::Monday);
            $endDate = $date->endOfWeek(WeekDay::Sunday);
        } else {
            $startDate = $date->startOfMonth();
            $endDate = $date->endOfMonth();
        }

        $rows = RegisteredHour::query()
            ->select('employee_id')
            ->selectRaw('SUM(total_heures) as total_heures')
            ->selectRaw('SUM(h_jour) as h_jour')
            ->selectRaw('SUM(h_nuit) as h_nuit')
            ->selectRaw('SUM(h_ferie_jour) as h_ferie_jour')
            ->selectRaw('SUM(h_ferie_nuit) as h_ferie_nuit')
            ->with('employee:id,matricule,nom,prenom')
            ->whereBetween('date_enreg', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('employee_id')
            ->orderBy('employee_id')
            ->get();

        return Inertia::render('Hours/Summary', [
            'rows' => $rows,
            'period' => $period,
            'date' => $date->toDateString(),
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
        ]);
    }
}
