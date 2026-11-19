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
            'code' => 'BL-100',
            'motif' => 'Motif corrigé',
            'resume' => 'Résumé corrigé',
            'attribue_par' => 'Responsable RH',
        ]);
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
