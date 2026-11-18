<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_create_a_complete_employee_record(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'rh']);

        $this->actingAs($user)->post('/employees', [
            'matricule' => 'RH-100',
            'site' => 'Cotonou',
            'nom' => 'Agon',
            'prenom' => 'Maurice',
            'naissance' => '1988-01-01',
            'genre' => 'H',
            'fonction' => 'Magasinier',
            'embauche' => '2010-01-01',
            'poste_specifique' => 'Collecteur',
            'hierarchie' => 'Exploitation',
            'categorie' => 'Agent',
            'actif' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'matricule' => 'RH-100',
            'naissance' => '1988-01-01',
            'poste_specifique' => 'Collecteur',
            'actif' => true,
        ]);
    }

    public function test_admin_can_update_and_deactivate_an_employee_without_deleting_history(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = Employee::create([
            'matricule' => 'RH-101',
            'site' => 'Cotonou',
            'nom' => 'Agon',
            'prenom' => 'Maurice',
            'genre' => 'H',
            'actif' => true,
        ]);

        $this->actingAs($admin)->put('/employees/'.$employee->id, [
            'matricule' => 'RH-101',
            'site' => 'Porto-Novo',
            'nom' => 'Agon',
            'prenom' => 'Maurice',
            'naissance' => null,
            'genre' => 'H',
            'fonction' => 'Magasinier',
            'embauche' => null,
            'poste_specifique' => null,
            'hierarchie' => null,
            'categorie' => null,
            'actif' => true,
        ])->assertRedirect();

        $this->patch('/employees/'.$employee->id.'/status')->assertRedirect();

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'site' => 'Porto-Novo', 'actif' => false]);
    }

    public function test_employee_role_cannot_access_employee_administration(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'employee']);

        $this->actingAs($user)->get('/employees')->assertForbidden();
    }

    public function test_birthdays_include_only_active_employees(): void
    {
        /** @var User $hr */
        $hr = User::factory()->create(['role' => 'rh']);
        Employee::create([
            'matricule' => 'RH-102',
            'site' => 'Cotonou',
            'nom' => 'Agon',
            'prenom' => 'Maurice',
            'naissance' => now()->format('Y-m-d'),
            'genre' => 'H',
            'actif' => true,
        ]);

        $this->actingAs($hr)->get('/employees/birthdays')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Employees/Birthdays')->has('birthdays', 1));
    }
}
