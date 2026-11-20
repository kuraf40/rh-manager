<?php

namespace Tests\Feature;

use App\Models\DisciplinaryAction;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisciplinaryActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_update_a_disciplinary_action(): void
    {
        /** @var User $hr */
        $hr = User::factory()->create(['role' => 'rh']);
        $action = $this->createAction();

        $this->actingAs($hr)->put(route('discipline.update', $action), [
            'type' => 'blame',
            'employee_id' => $action->employee_id,
            'code' => 'BL-100',
            'motif' => 'Motif corrigé',
            'resume' => 'Résumé corrigé',
            'nb_jours' => null,
            'attribue_par' => 'Responsable RH',
        ])->assertRedirect()->assertSessionHas('message', 'Action disciplinaire mise à jour.');

        $this->assertDatabaseHas('disciplinary_actions', [
            'id' => $action->id,
            'type' => 'blame',
            'code' => null,
            'motif' => 'Motif corrigé',
            'resume' => 'Résumé corrigé',
            'attribue_par' => 'Responsable RH',
        ]);
    }

    public function test_history_search_filters_actions_by_employee_details(): void
    {
        /** @var User $hr */
        $hr = User::factory()->create(['role' => 'rh']);
        $action = $this->createAction();
        $otherEmployee = Employee::create([
            'matricule' => 'DISC-OTHER',
            'site' => 'Cotonou',
            'nom' => 'Houngbo',
            'prenom' => 'Paul',
            'genre' => 'H',
            'actif' => true,
        ]);
        DisciplinaryAction::create([
            'type' => 'blame',
            'employee_id' => $otherEmployee->id,
            'motif' => 'Absence',
        ]);

        $this->actingAs($hr)->get(route('discipline.index', ['search' => 'DISC-200']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Discipline/Index')
                ->where('search', 'DISC-200')
                ->where('actions.total', 1)
                ->where('actions.data.0.id', $action->id)
                ->where('actions.data.0.employee.matricule', 'DISC-200'));
    }

    public function test_history_search_does_not_treat_input_as_sql(): void
    {
        /** @var User $hr */
        $hr = User::factory()->create(['role' => 'rh']);
        $this->createAction();

        $this->actingAs($hr)->get(route('discipline.index', ['search' => "' OR 1=1 --"]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Discipline/Index')
                ->where('actions.total', 0));
    }

    public function test_hr_can_delete_a_disciplinary_action(): void
    {
        /** @var User $hr */
        $hr = User::factory()->create(['role' => 'rh']);
        $action = $this->createAction();

        $this->actingAs($hr)->delete(route('discipline.destroy', $action))
            ->assertRedirect()
            ->assertSessionHas('message', 'Action disciplinaire supprimée.');

        $this->assertDatabaseMissing('disciplinary_actions', ['id' => $action->id]);
    }

    public function test_employee_cannot_update_a_disciplinary_action(): void
    {
        /** @var User $employeeUser */
        $employeeUser = User::factory()->create(['role' => 'employee']);
        $action = $this->createAction();

        $this->actingAs($employeeUser)->put(route('discipline.update', $action), [
            'type' => 'blame',
            'employee_id' => $action->employee_id,
            'motif' => 'Modification interdite',
        ])->assertForbidden();

        $this->assertDatabaseHas('disciplinary_actions', [
            'id' => $action->id,
            'motif' => 'Retard',
        ]);
    }

    public function test_employee_cannot_delete_a_disciplinary_action(): void
    {
        /** @var User $employeeUser */
        $employeeUser = User::factory()->create(['role' => 'employee']);
        $action = $this->createAction();

        $this->actingAs($employeeUser)->delete(route('discipline.destroy', $action))->assertForbidden();

        $this->assertDatabaseHas('disciplinary_actions', ['id' => $action->id]);
    }

    private function createAction(): DisciplinaryAction
    {
        $employee = Employee::create([
            'matricule' => 'DISC-200',
            'site' => 'Cotonou',
            'nom' => 'Agon',
            'prenom' => 'Maurice',
            'genre' => 'H',
            'actif' => true,
        ]);

        return DisciplinaryAction::create([
            'type' => 'avertissement',
            'employee_id' => $employee->id,
            'motif' => 'Retard',
            'attribue_par' => 'RH',
        ]);
    }
}
