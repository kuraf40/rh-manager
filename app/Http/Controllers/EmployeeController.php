<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $employees = Employee::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('matricule', 'like', "%{$search}%");
                });
            })
            ->orderBy('nom')
            ->orderBy('prenom')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->employeeRules());
        $validated['actif'] = $validated['actif'] ?? true;

        Employee::create($validated);

        return back()->with('message', 'Employé créé avec succès.');
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validate($this->employeeRules($employee)));

        return back()->with('message', 'Employé mis à jour avec succès.');
    }

    public function updateStatus(Employee $employee): RedirectResponse
    {
        $employee->update(['actif' => ! $employee->actif]);

        return back()->with('message', $employee->actif ? 'Employé réactivé.' : 'Employé désactivé.');
    }

    public function birthdays(): Response
    {
        $today = CarbonImmutable::today();
        $birthdays = Employee::query()
            ->where('actif', true)
            ->whereNotNull('naissance')
            ->get(['id', 'matricule', 'nom', 'prenom', 'fonction', 'naissance'])
            ->map(function (Employee $employee) use ($today): ?array {
                $birthDate = $this->parseLegacyDate($employee->naissance);

                if ($birthDate === null) {
                    return null;
                }

                $nextBirthday = CarbonImmutable::create($today->year, $birthDate->month, $birthDate->day)->startOfDay();

                if ($nextBirthday->lessThan($today)) {
                    $nextBirthday = $nextBirthday->addYear();
                }

                return [
                    'id' => $employee->id,
                    'matricule' => $employee->matricule,
                    'nom' => $employee->nom,
                    'prenom' => $employee->prenom,
                    'fonction' => $employee->fonction,
                    'date' => $nextBirthday->format('m-d'),
                    'daysUntil' => $today->diffInDays($nextBirthday),
                ];
            })
            ->filter()
            ->sortBy('daysUntil')
            ->values();

        return Inertia::render('Employees/Birthdays', ['birthdays' => $birthdays]);
    }

    private function employeeRules(?Employee $employee = null): array
    {
        return [
            'matricule' => ['required', 'string', 'max:20', Rule::unique('employees', 'matricule')->ignore($employee?->id)],
            'site' => ['required', 'string', 'max:50'],
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'naissance' => ['nullable', 'date_format:Y-m-d'],
            'genre' => ['required', Rule::in(['H', 'F'])],
            'fonction' => ['nullable', 'string', 'max:150'],
            'embauche' => ['nullable', 'date_format:Y-m-d'],
            'poste_specifique' => ['nullable', 'string', 'max:150'],
            'hierarchie' => ['nullable', 'string', 'max:150'],
            'categorie' => ['nullable', 'string', 'max:50'],
            'actif' => ['sometimes', 'boolean'],
        ];
    }

    private function parseLegacyDate(?string $date): ?CarbonImmutable
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        foreach (['!Y-m-d', '!d/m/Y'] as $format) {
            try {
                $parsed = CarbonImmutable::createFromFormat($format, $date);
            } catch (\Throwable) {
                $parsed = false;
            }

            if ($parsed !== false) {
                return $parsed;
            }
        }

        return null;
    }
}
