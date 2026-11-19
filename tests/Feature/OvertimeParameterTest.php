<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OvertimeParameterTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_update_legacy_overtime_rates(): void
    {
        /** @var User $hr */
        $hr = User::factory()->create(['role' => 'rh']);

        $this->actingAs($hr)->put('/hours/settings', [
            'hs_12' => 12,
            'hs_35' => 35,
            'hs_50' => 50,
            'hs_100' => 100,
        ])->assertRedirect()->assertSessionHas('message', 'Paramètres de majoration enregistrés.');

        $this->assertDatabaseHas('overtime_parameters', [
            'id' => 1,
            'hs_12' => 12,
            'hs_35' => 35,
            'hs_50' => 50,
            'hs_100' => 100,
        ]);
    }

    public function test_employee_role_cannot_read_or_update_overtime_rates(): void
    {
        /** @var User $employee */
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)->get('/hours/settings')->assertForbidden();
        $this->actingAs($employee)->put('/hours/settings', [])->assertForbidden();
    }

    public function test_hr_cannot_save_a_rate_that_exceeds_the_database_precision(): void
    {
        /** @var User $hr */
        $hr = User::factory()->create(['role' => 'rh']);

        $this->actingAs($hr)->put('/hours/settings', [
            'hs_12' => 1000,
            'hs_35' => 35,
            'hs_50' => 50,
            'hs_100' => 100,
        ])->assertSessionHasErrors([
            'hs_12' => 'Le taux ne peut pas dépasser 999,99 %.',
        ]);

        $this->assertDatabaseCount('overtime_parameters', 0);
    }
}
