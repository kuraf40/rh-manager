<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\OvertimeParameter;
use App\Models\RegisteredHour;
use App\Services\OvertimeRateCalculator;
use App\Services\OvertimeRegistrationService;
use Carbon\CarbonImmutable;
use Carbon\WeekDay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredHourController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);
        $search = trim($validated['search'] ?? '');

        return Inertia::render('Hours/Index', [
            'employees' => Employee::query()
                ->where('actif', true)
                ->whereRaw('LOWER(fonction) = ?', ['magasinier'])
                ->orderBy('nom')
                ->get(['id', 'matricule', 'nom', 'prenom']),
            'editableEmployees' => Employee::query()
                ->orderBy('nom')
                ->orderBy('prenom')
                ->get(['id', 'matricule', 'nom', 'prenom']),
            'hours' => RegisteredHour::query()
                ->with('employee:id,matricule,nom,prenom')
                ->when($search !== '', function (Builder $query) use ($search): void {
                    $pattern = "%{$search}%";

                    $query->where(function (Builder $query) use ($pattern): void {
                        $query->whereHas('employee', function (Builder $employeeQuery) use ($pattern): void {
                            $employeeQuery->where('nom', 'like', $pattern)
                                ->orWhere('prenom', 'like', $pattern)
                                ->orWhere('matricule', 'like', $pattern)
                                ->orWhere('fonction', 'like', $pattern);
                        })
                            ->orWhere('date_enreg', 'like', $pattern)
                            ->orWhere('heure_debut', 'like', $pattern)
                            ->orWhere('heure_fin', 'like', $pattern)
                            ->orWhere('vacation', 'like', $pattern)
                            ->orWhere('type_jour', 'like', $pattern);
                    });
                })
                ->orderByDesc('date_enreg')
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString(),
            'search' => $search,
            'today' => now()->toDateString(),
        ]);
    }

    public function store(Request $request, OvertimeRegistrationService $overtimeRegistration): RedirectResponse
    {
        $validated = $this->validatedHourInput($request);

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

    public function update(
        Request $request,
        RegisteredHour $registeredHour,
        OvertimeRegistrationService $overtimeRegistration,
    ): RedirectResponse {
        $validated = $this->validatedHourInput($request, true);
        $segments = $overtimeRegistration->buildSegments(
            $validated['date_enreg'],
            $validated['heure_debut'],
            $validated['heure_fin'],
            $validated['vacation'],
            $validated['type_jour'],
        );

        if (count($segments) !== 1) {
            throw ValidationException::withMessages([
                'heure_fin' => 'La modification doit rester sur une seule date. Modifiez séparément chaque ligne traversant minuit.',
            ]);
        }

        $weeklyLimitWarning = $overtimeRegistration->weeklyLimitWarning(
            (int) $validated['employee_id'],
            $segments,
            (int) $registeredHour->getKey(),
        );

        if ($weeklyLimitWarning !== null && ! ($validated['forcer'] ?? false)) {
            throw ValidationException::withMessages(['forcer' => $weeklyLimitWarning]);
        }

        DB::transaction(function () use ($validated, $registeredHour, $segments): void {
            $registeredHour->update([
                'employee_id' => $validated['employee_id'],
                ...$segments[0],
            ]);
        });

        return back()->with('message', 'Saisie d’heures mise à jour.');
    }

    public function destroy(RegisteredHour $registeredHour): RedirectResponse
    {
        $registeredHour->delete();

        return back()->with('message', 'Saisie d’heures supprimée.');
    }

    public function summary(Request $request, OvertimeRateCalculator $rateCalculator): Response
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
            $referenceMonth = $date->startOfMonth();
            $startDate = $referenceMonth->subMonthNoOverflow()->setDay(20);
            $endDate = $referenceMonth->setDay(19);
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
            ->get()
            ->map(function (RegisteredHour $row) use ($rateCalculator): RegisteredHour {
                $rateHours = $rateCalculator->calculate(
                    (float) $row->h_jour,
                    (float) $row->h_nuit,
                    (float) $row->h_ferie_jour,
                    (float) $row->h_ferie_nuit,
                );

                foreach ($rateHours as $rate => $hours) {
                    $row->setAttribute($rate, $hours);
                }

                return $row;
            });

        return Inertia::render('Hours/Summary', [
            'rows' => $rows,
            'rates' => OvertimeParameter::current()->only(['hs_12', 'hs_35', 'hs_50', 'hs_100']),
            'period' => $period,
            'date' => $date->toDateString(),
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
        ]);
    }

    /**
     * @return array{
     *     employee_id: int,
     *     date_enreg: string,
     *     vacation: string,
     *     heure_debut: string,
     *     heure_fin: string,
     *     type_jour: string,
     *     forcer?: bool
     * }
     */
    private function validatedHourInput(Request $request, bool $allowAnyEmployee = false): array
    {
        $employeeRule = Rule::exists(Employee::class, 'id');

        if (! $allowAnyEmployee) {
            $employeeRule->where(function ($query): void {
                $query->where('actif', true)
                    ->whereRaw('LOWER(fonction) = ?', ['magasinier']);
            });
        }

        $validated = $request->validate([
            'employee_id' => ['required', $employeeRule],
            'date_enreg' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'vacation' => ['required', Rule::in(['07:00-15:00', '15:00-23:00', '23:00-07:00'])],
            'heure_debut' => ['required', 'date_format:H:i'],
            'heure_fin' => ['required', 'date_format:H:i'],
            'type_jour' => ['required', Rule::in(['ferie', 'non_ferie'])],
            'forcer' => ['sometimes', 'boolean'],
        ], [
            'employee_id.required' => $allowAnyEmployee ? 'Sélectionnez un employé.' : 'Sélectionnez un magasinier actif.',
            'employee_id.exists' => $allowAnyEmployee ? 'L’employé sélectionné est invalide.' : 'Sélectionnez un magasinier actif.',
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

        $validated['employee_id'] = (int) $validated['employee_id'];

        return $validated;
    }
}
