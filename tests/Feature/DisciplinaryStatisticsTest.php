<?php

namespace Tests\Feature;

use App\Models\DisciplinaryAction;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisciplinaryStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_view_counts_per_disciplinary_action_type(): void
    {
        /** @var User $hr */
        $hr = User::factory()->create(['role' => 'rh']);
        $employee = Employee::create([
            'matricule' => 'DISC-100',
            'site' => 'Cotonou',
            'nom' => 'Agon',
            'prenom' => 'Maurice',
            'genre' => 'H',
            'actif' => true,
        ]);
        DisciplinaryAction::create([
            'type' => 'avertissement',
            'employee_id' => $employee->id,
            'motif' => 'Retard',
        ]);
        DisciplinaryAction::create([
            'type' => 'blame',
            'employee_id' => $employee->id,
            'motif' => 'Absence',
        ]);

        $this->actingAs($hr)->get('/discipline/statistics')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Discipline/Statistics')
                ->where('employees.0.matricule', 'DISC-100')
                ->where('employees.0.avertissement_count', 1)
                ->where('employees.0.blame_count', 1)
                ->where('employees.0.total_actions', 2));
    }

    public function test_employee_role_cannot_view_disciplinary_statistics(): void
    {
        /** @var User $employee */
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)->get('/discipline/statistics')->assertForbidden();
    }
}
