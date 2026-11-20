<?php

namespace App\Http\Controllers;

use App\Models\DisciplinaryAction;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DisciplinaryActionController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);
        $search = trim($validated['search'] ?? '');

        return Inertia::render('Discipline/Index', [
            'employees' => Employee::orderBy('nom')->get(),
            'actions' => DisciplinaryAction::with('employee')
                ->when($search !== '', function (Builder $query) use ($search): void {
                    $pattern = "%{$search}%";

                    $query->where(function (Builder $query) use ($pattern): void {
                        $query->whereHas('employee', function (Builder $employeeQuery) use ($pattern): void {
                            $employeeQuery->where('nom', 'like', $pattern)
                                ->orWhere('prenom', 'like', $pattern)
                                ->orWhere('matricule', 'like', $pattern)
                                ->orWhere('fonction', 'like', $pattern);
                        })
                            ->orWhere('type', 'like', $pattern)
                            ->orWhere('motif', 'like', $pattern)
                            ->orWhere('resume', 'like', $pattern)
                            ->orWhere('attribue_par', 'like', $pattern)
                            ->orWhere('date_enregistrement', 'like', $pattern);
                    });
                })
                ->orderByDesc('date_enregistrement')
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function statistics(): Response
    {
        $employees = Employee::query()
            ->select(['id', 'matricule', 'nom', 'prenom', 'fonction', 'actif'])
            ->withCount([
                'disciplinaryActions as demande_explication_count' => fn ($query) => $query->where('type', 'demande_explication'),
                'disciplinaryActions as avertissement_count' => fn ($query) => $query->where('type', 'avertissement'),
                'disciplinaryActions as blame_count' => fn ($query) => $query->where('type', 'blame'),
                'disciplinaryActions as mise_a_pied_count' => fn ($query) => $query->where('type', 'mise_a_pied'),
            ])
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get()
            ->map(function (Employee $employee): Employee {
                $employee->setAttribute('total_actions',
                    $employee->demande_explication_count +
                    $employee->avertissement_count +
                    $employee->blame_count +
                    $employee->mise_a_pied_count,
                );

                return $employee;
            });

        return Inertia::render('Discipline/Statistics', ['employees' => $employees]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->actionRules());

        $validated['date_enregistrement'] = now();
        DisciplinaryAction::create($validated);

        return redirect()->back()->with('message', 'Sanction enregistrée avec succès.');
    }

    public function update(Request $request, DisciplinaryAction $disciplinaryAction): RedirectResponse
    {
        $disciplinaryAction->update($request->validate($this->actionRules()));

        return back()->with('message', 'Action disciplinaire mise à jour.');
    }

    public function destroy(DisciplinaryAction $disciplinaryAction): RedirectResponse
    {
        $disciplinaryAction->delete();

        return back()->with('message', 'Action disciplinaire supprimée.');
    }

    /**
     * @return array<string, list<string>>
     */
    private function actionRules(): array
    {
        return [
            'type' => ['required', 'in:demande_explication,avertissement,blame,mise_a_pied'],
            'employee_id' => ['required', 'exists:employees,id'],
            'motif' => ['required', 'string'],
            'resume' => ['nullable', 'string'],
            'nb_jours' => ['nullable', 'integer', 'min:1'],
            'attribue_par' => ['nullable', 'string', 'max:100'],
        ];
    }
}
