<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\RegisteredHour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisteredHourTest extends TestCase
{
    use RefreshDatabase;

    public function test_hour_entry_stores_net_duration_and_matching_hour_categories(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = $this->createEmployee('HOUR-100');

        $this->actingAs($admin)->post('/hours', [
            'employee_id' => $employee->id,
            'date_enreg' => '2026-09-28',
            'heure_debut' => '08:00',
            'heure_fin' => '17:00',
            'pause_minutes' => 60,
            'h_jour' => 8,
            'h_nuit' => 0,
            'h_ferie_jour' => 0,
            'h_ferie_nuit' => 0,
        ])->assertRedirect();

        $this->assertDatabaseHas('registered_hours', [
            'employee_id' => $employee->id,
            'total_heures' => 8,
            'h_jour' => 8,
        ]);
    }

    public function test_hour_entry_supports_overnight_work_and_rejects_mismatched_categories(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = $this->createEmployee('HOUR-101');
        $payload = [
            'employee_id' => $employee->id,
            'date_enreg' => '2026-09-28',
            'heure_debut' => '22:00',
            'heure_fin' => '02:00',
            'pause_minutes' => 0,
            'h_jour' => 0,
            'h_nuit' => 4,
            'h_ferie_jour' => 0,
            'h_ferie_nuit' => 0,
        ];

        $this->actingAs($admin)->post('/hours', $payload)->assertRedirect();

        $this->assertDatabaseHas('registered_hours', [
            'employee_id' => $employee->id,
            'total_heures' => 4,
            'h_nuit' => 4,
        ]);

        $payload['h_nuit'] = 3;
        $this->post('/hours', $payload)->assertSessionHasErrors('h_jour');
        $this->assertDatabaseCount('registered_hours', 1);
    }

    public function test_month_report_groups_hour_categories_for_the_selected_period(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = $this->createEmployee('HOUR-102');
        RegisteredHour::create([
            'employee_id' => $employee->id,
            'date_enreg' => '2026-09-12',
            'heure_debut' => '22:00',
            'heure_fin' => '02:00',
            'total_heures' => 4,
            'h_jour' => 0,
            'h_nuit' => 2,
            'h_ferie_jour' => 1,
            'h_ferie_nuit' => 1,
            'mois_reference' => '2026-09',
            'semaine_reference' => 37,
        ]);
        RegisteredHour::create([
            'employee_id' => $employee->id,
            'date_enreg' => '2026-08-31',
            'heure_debut' => '08:00',
            'heure_fin' => '12:00',
            'total_heures' => 4,
            'h_jour' => 4,
            'h_nuit' => 0,
            'h_ferie_jour' => 0,
            'h_ferie_nuit' => 0,
            'mois_reference' => '2026-08',
            'semaine_reference' => 36,
        ]);

        $this->actingAs($admin)->get('/hours/summary?period=month&date=2026-09-15')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Hours/Summary')
                ->where('rows.0.employee.matricule', 'HOUR-102')
                ->where('rows.0.total_heures', 4)
                ->where('rows.0.h_ferie_nuit', 1)
                ->where('startDate', '2026-09-01')
                ->where('endDate', '2026-09-30'));
    }

    public function test_employee_role_cannot_view_all_employee_hours(): void
    {
        /** @var User $employeeUser */
        $employeeUser = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employeeUser)->get('/hours/summary')->assertForbidden();
    }

    private function createEmployee(string $matricule): Employee
    {
        return Employee::create([
            'matricule' => $matricule,
            'site' => 'Cotonou',
            'nom' => 'Agon',
            'prenom' => 'Maurice',
            'genre' => 'H',
            'actif' => true,
        ]);
    }
}
