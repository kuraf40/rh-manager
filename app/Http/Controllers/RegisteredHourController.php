<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\RegisteredHour;
use App\Services\OvertimeRegistrationService;
use Carbon\CarbonImmutable;
use Carbon\WeekDay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredHourController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Hours/Index', [
            'employees' => Employee::query()
                ->where('actif', true)
                ->whereRaw('LOWER(fonction) = ?', ['magasinier'])
                ->orderBy('nom')
                ->get(['id', 'matricule', 'nom', 'prenom']),
            'recentHours' => RegisteredHour::with('employee')
                ->latest()
                ->take(10)
                ->get(),
            'today' => now()->toDateString(),
        ]);
    }

    public function store(Request $request, OvertimeRegistrationService $overtimeRegistration): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists(Employee::class, 'id')->where(function ($query): void {
                    $query->where('actif', true)
                        ->whereRaw('LOWER(fonction) = ?', ['magasinier']);
                }),
            ],
            'date_enreg' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'vacation' => ['required', Rule::in(['07:00-15:00', '15:00-23:00', '23:00-07:00'])],
            'heure_debut' => ['required', 'date_format:H:i'],
            'heure_fin' => ['required', 'date_format:H:i'],
            'type_jour' => ['required', Rule::in(['ferie', 'non_ferie'])],
            'forcer' => ['sometimes', 'boolean'],
        ], [
            'employee_id.required' => 'Sélectionnez un magasinier actif.',
            'employee_id.exists' => 'Sélectionnez un magasinier actif.',
            'date_enreg.required' => 'La date est obligatoire.',
            'date_enreg.before_or_equal' => 'La date ne peut pas être dans le futur.',
            'vacation.required' => 'Sélectionnez une vacation.',
            'vacation.in' => 'La vacation sélectionnée est invalide.',
            'heure_debut.required' => 'L’heure de début est obligatoire.',
            'heure_fin.required' => 'L’heure de fin est obligatoire.',
            'type_jour.required' => 'Indiquez si le jour est férié.',
            'type_jour.in' => 'Le type de jour sélectionné est invalide.',
        ]);

        foreach (['heure_debut', 'heure_fin'] as $timeField) {
            if ((int) substr($validated[$timeField], 3, 2) % 30 !== 0) {
                throw ValidationException::withMessages([
                    $timeField => 'Les heures doivent être saisies par tranches de 30 minutes.',
                ]);
            }
        }

        $segments = $overtimeRegistration->buildSegments(
            $validated['date_enreg'],
            $validated['heure_debut'],
            $validated['heure_fin'],
            $validated['vacation'],
            $validated['type_jour'],
        );
        $weeklyLimitWarning = $overtimeRegistration->weeklyLimitWarning(
            (int) $validated['employee_id'],
            $segments,
        );

        if ($weeklyLimitWarning !== null && ! ($validated['forcer'] ?? false)) {
            throw ValidationException::withMessages(['forcer' => $weeklyLimitWarning]);
        }

        DB::transaction(function () use ($validated, $segments): void {
            foreach ($segments as $segment) {
                RegisteredHour::create([
                    'employee_id' => $validated['employee_id'],
                    ...$segment,
                ]);
            }
        });

        return back()->with('message', 'Heures supplémentaires enregistrées avec succès.');
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
