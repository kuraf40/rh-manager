<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\OvertimeParameter;
use App\Models\RegisteredHour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisteredHourTest extends TestCase
{
    use RefreshDatabase;

    public function test_holiday_break_time_is_saved_with_automatically_calculated_categories(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = $this->createEmployee('HOUR-100');

        $this->actingAs($admin)->post('/hours', [
            'employee_id' => $employee->id,
            'date_enreg' => '2026-09-28',
            'vacation' => '07:00-15:00',
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'type_jour' => 'ferie',
        ])->assertRedirect();

        $this->assertDatabaseHas('registered_hours', [
            'employee_id' => $employee->id,
            'date_enreg' => '2026-09-28',
            'vacation' => '07:00-15:00',
            'type_jour' => 'ferie',
            'total_heures' => 1,
            'h_ferie_jour' => 1,
            'h_jour' => 0,
            'mois_reference' => '2026-09',
            'semaine_reference' => 2,
        ]);
    }

    public function test_overtime_crossing_midnight_is_split_into_its_calendar_days(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = $this->createEmployee('HOUR-101');

        $this->actingAs($admin)->post('/hours', [
            'employee_id' => $employee->id,
            'date_enreg' => '2026-09-28',
            'vacation' => '15:00-23:00',
            'heure_debut' => '23:30',
            'heure_fin' => '00:30',
            'type_jour' => 'non_ferie',
        ])->assertRedirect();

        $this->assertDatabaseHas('registered_hours', [
            'employee_id' => $employee->id,
            'date_enreg' => '2026-09-28',
            'heure_debut' => '23:30',
            'heure_fin' => '00:00',
            'total_heures' => 0.5,
            'h_nuit' => 0.5,
        ]);
        $this->assertDatabaseHas('registered_hours', [
            'employee_id' => $employee->id,
            'date_enreg' => '2026-09-29',
            'heure_debut' => '00:00',
            'heure_fin' => '00:30',
            'total_heures' => 0.5,
            'h_nuit' => 0.5,
        ]);
    }

    public function test_overtime_outside_authorized_breaks_is_rejected(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = $this->createEmployee('HOUR-103');

        $this->actingAs($admin)->post('/hours', [
            'employee_id' => $employee->id,
            'date_enreg' => '2026-09-28',
            'vacation' => '07:00-15:00',
            'heure_debut' => '14:00',
            'heure_fin' => '15:00',
            'type_jour' => 'non_ferie',
        ])->assertSessionHasErrors('heure_debut');

        $this->assertDatabaseCount('registered_hours', 0);
    }

    public function test_overtime_rejects_future_dates(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = $this->createEmployee('HOUR-109');
        $this->actingAs($admin)->post('/hours', [
            'employee_id' => $employee->id,
            'date_enreg' => now()->addDay()->toDateString(),
            'vacation' => '07:00-15:00',
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'type_jour' => 'non_ferie',
        ])->assertSessionHasErrors([
            'date_enreg' => 'La date ne peut pas être dans le futur.',
        ]);

        $this->assertDatabaseCount('registered_hours', 0);
    }

    public function test_overtime_requires_half_hour_time_steps(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = $this->createEmployee('HOUR-110');

        $this->actingAs($admin)->post('/hours', [
            'employee_id' => $employee->id,
            'date_enreg' => '2026-09-28',
            'vacation' => '07:00-15:00',
            'heure_debut' => '10:15',
            'heure_fin' => '11:00',
            'type_jour' => 'non_ferie',
        ])->assertSessionHasErrors([
            'heure_debut' => 'Les heures doivent être saisies par tranches de 30 minutes.',
        ]);

        $this->assertDatabaseCount('registered_hours', 0);
    }

    public function test_night_vacation_overtime_is_classified_as_night_hours(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = $this->createEmployee('HOUR-107');

        $this->actingAs($admin)->post('/hours', [
            'employee_id' => $employee->id,
            'date_enreg' => '2026-09-28',
            'vacation' => '23:00-07:00',
            'heure_debut' => '06:00',
            'heure_fin' => '07:00',
            'type_jour' => 'non_ferie',
        ])->assertRedirect();

        $this->assertDatabaseHas('registered_hours', [
            'employee_id' => $employee->id,
            'total_heures' => 1,
            'h_jour' => 0,
            'h_nuit' => 1,
        ]);
    }

    public function test_weekly_limit_requires_confirmation_before_saving(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = $this->createEmployee('HOUR-104');
        RegisteredHour::create([
            'employee_id' => $employee->id,
            'date_enreg' => '2026-09-28',
            'heure_debut' => '09:00',
            'heure_fin' => '09:30',
            'total_heures' => 4.5,
            'h_jour' => 4.5,
            'h_nuit' => 0,
            'h_ferie_jour' => 0,
            'h_ferie_nuit' => 0,
            'mois_reference' => '2026-09',
            'semaine_reference' => 2,
        ]);
        $payload = [
            'employee_id' => $employee->id,
            'date_enreg' => '2026-09-28',
            'vacation' => '07:00-15:00',
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'type_jour' => 'non_ferie',
        ];

        $this->actingAs($admin)->post('/hours', $payload)->assertSessionHasErrors([
            'forcer' => 'Cette saisie porterait le total à 5,50 h pour la semaine 2 du mois de référence 2026-09 (limite : 5 h). Confirmer l’enregistrement ?',
        ]);
        $this->assertDatabaseCount('registered_hours', 1);

        $this->post('/hours', [...$payload, 'forcer' => true])->assertRedirect();
        $this->assertDatabaseCount('registered_hours', 2);
    }

    public function test_only_active_magasinier_can_be_selected_for_overtime(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $eligibleEmployee = $this->createEmployee('HOUR-105');
        $otherEmployee = $this->createEmployee('HOUR-106');
        $otherEmployee->update(['fonction' => 'Agent']);
        $inactiveEmployee = $this->createEmployee('HOUR-108');
        $inactiveEmployee->update(['actif' => false]);

        $this->actingAs($admin)->get('/hours')
            ->assertInertia(fn ($page) => $page->component('Hours/Index')
                ->has('employees', 1)
                ->where('employees.0.id', $eligibleEmployee->id));

        $this->post('/hours', [
            'employee_id' => $otherEmployee->id,
            'date_enreg' => '2026-09-28',
            'vacation' => '07:00-15:00',
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'type_jour' => 'non_ferie',
        ])->assertSessionHasErrors('employee_id');

        $this->post('/hours', [
            'employee_id' => $inactiveEmployee->id,
            'date_enreg' => '2026-09-28',
            'vacation' => '07:00-15:00',
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'type_jour' => 'non_ferie',
        ])->assertSessionHasErrors('employee_id');

        $this->assertDatabaseCount('registered_hours', 0);
    }

    public function test_month_report_uses_legacy_cycle_and_calculates_hours_by_rate(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = $this->createEmployee('HOUR-102');
        OvertimeParameter::query()->create([
            'hs_12' => 12,
            'hs_35' => 35,
            'hs_50' => 50,
            'hs_100' => 100,
        ]);
        $hourRows = [
            [
                'date_enreg' => '2026-08-20',
                'heure_debut' => '08:00',
                'heure_fin' => '14:00',
                'total_heures' => 6,
                'h_jour' => 6,
                'h_nuit' => 0,
                'h_ferie_jour' => 0,
                'h_ferie_nuit' => 0,
            ],
            [
                'date_enreg' => '2026-08-31',
                'heure_debut' => '08:00',
                'heure_fin' => '12:00',
                'total_heures' => 4,
                'h_jour' => 4,
                'h_nuit' => 0,
                'h_ferie_jour' => 0,
                'h_ferie_nuit' => 0,
            ],
            [
                'date_enreg' => '2026-09-12',
                'heure_debut' => '22:00',
                'heure_fin' => '02:00',
                'total_heures' => 4,
                'h_jour' => 0,
                'h_nuit' => 2,
                'h_ferie_jour' => 1,
                'h_ferie_nuit' => 1,
            ],
            [
                'date_enreg' => '2026-09-19',
                'heure_debut' => '10:00',
                'heure_fin' => '11:00',
                'total_heures' => 1,
                'h_jour' => 1,
                'h_nuit' => 0,
                'h_ferie_jour' => 0,
                'h_ferie_nuit' => 0,
            ],
            [
                'date_enreg' => '2026-08-19',
                'heure_debut' => '08:00',
                'heure_fin' => '10:00',
                'total_heures' => 2,
                'h_jour' => 2,
                'h_nuit' => 0,
                'h_ferie_jour' => 0,
                'h_ferie_nuit' => 0,
            ],
            [
                'date_enreg' => '2026-09-20',
                'heure_debut' => '08:00',
                'heure_fin' => '11:00',
                'total_heures' => 3,
                'h_jour' => 3,
                'h_nuit' => 0,
                'h_ferie_jour' => 0,
                'h_ferie_nuit' => 0,
            ],
        ];

        foreach ($hourRows as $hourRow) {
            RegisteredHour::create([
                'employee_id' => $employee->id,
                ...$hourRow,
                'mois_reference' => '2026-09',
                'semaine_reference' => 2,
            ]);
        }

        $this->actingAs($admin)->get('/hours/summary?period=month&date=2026-09-15')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Hours/Summary')
                ->where('rows.0.employee.matricule', 'HOUR-102')
                ->where('rows.0.total_heures', 15)
                ->where('rows.0.h_jour', 11)
                ->where('rows.0.h_ferie_nuit', 1)
                ->where('rows.0.hs_12', 8)
                ->where('rows.0.hs_35', 3)
                ->where('rows.0.hs_50', 3)
                ->where('rows.0.hs_100', 1)
                ->where('rates.hs_12', 12)
                ->where('startDate', '2026-08-20')
                ->where('endDate', '2026-09-19'));
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
            'fonction' => 'Magasinier',
            'actif' => true,
        ]);
    }
}
