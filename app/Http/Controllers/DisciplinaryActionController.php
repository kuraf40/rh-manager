<?php

namespace App\Http\Controllers;

use App\Models\DisciplinaryAction;
use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DisciplinaryActionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Discipline/Index', [
            'employees' => Employee::orderBy('nom')->get(),
            'actions' => DisciplinaryAction::with('employee')
                ->orderByDesc('date_enregistrement')
                ->orderByDesc('id')
                ->paginate(10),
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:demande_explication,avertissement,blame,mise_a_pied',
            'employee_id' => 'required|exists:employees,id',
            'code' => 'nullable|string|max:20',
            'motif' => 'required|string',
            'resume' => 'nullable|string',
            'nb_jours' => 'nullable|integer|min:1',
            'attribue_par' => 'nullable|string|max:100',
        ]);

        $validated['date_enregistrement'] = now();
        DisciplinaryAction::create($validated);

        return redirect()->back()->with('message', 'Sanction enregistrée avec succès.');
    }
}
