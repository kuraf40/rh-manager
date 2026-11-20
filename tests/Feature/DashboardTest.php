<?php

namespace Tests\Feature;

use App\Models\DisciplinaryAction;
use App\Models\Employee;
use App\Models\RegisteredHour;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_recent_overtime_and_disciplinary_charts(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->travelTo(CarbonImmutable::parse('2026-11-20 12:00:00'));
        $employee = Employee::create([
            'matricule' => 'DASH-100',
            'site' => 'Cotonou',
            'nom' => 'Agon',
            'prenom' => 'Maurice',
            'genre' => 'H',
            'actif' => true,
        ]);
        RegisteredHour::create([
            'employee_id' => $employee->id,
            'date_enreg' => '2026-11-02',
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'total_heures' => 2,
            'h_jour' => 2,
            'h_nuit' => 0,
            'h_ferie_jour' => 0,
            'h_ferie_nuit' => 0,
        ]);
        RegisteredHour::create([
            'employee_id' => $employee->id,
            'date_enreg' => '2026-05-31',
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'total_heures' => 5,
            'h_jour' => 5,
            'h_nuit' => 0,
            'h_ferie_jour' => 0,
            'h_ferie_nuit' => 0,
        ]);
        DisciplinaryAction::create([
            'type' => 'blame',
            'employee_id' => $employee->id,
            'motif' => 'Retard',
        ]);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Dashboard')
                ->where('totalEmployees', 1)
                ->where('totalHours', 7)
                ->where('totalSanctions', 1)
                ->has('monthlyHours', 6)
                ->where('monthlyHours.5.month', '2026-11')
                ->where('monthlyHours.5.totalHours', 2)
                ->has('sanctionsByType', 4)
                ->where('sanctionsByType.2.type', 'blame')
                ->where('sanctionsByType.2.total', 1));
    }
}
