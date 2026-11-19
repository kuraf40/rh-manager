<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_manager_account_linked_to_an_employee(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = Employee::create([
            'matricule' => 'USER-100',
            'site' => 'Cotonou',
            'nom' => 'Agon',
            'prenom' => 'Maurice',
            'genre' => 'H',
            'actif' => true,
        ]);

        $this->actingAs($admin)->post('/users', [
            'name' => 'Maurice Agon',
            'email' => 'maurice@example.test',
            'password' => 'A-long-test-password-1',
            'password_confirmation' => 'A-long-test-password-1',
            'role' => 'manager',
            'employee_id' => $employee->id,
        ])->assertRedirect();

        $user = User::query()->where('email', 'maurice@example.test')->firstOrFail();
        $this->assertSame('manager', $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'user_id' => $user->id]);
    }

    public function test_account_form_offers_only_admin_hr_and_manager_roles(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/users/create')
            ->assertInertia(fn ($page) => $page->component('Users/Create')
                ->where('roles', ['admin', 'rh', 'manager']));
    }

    public function test_admin_cannot_create_an_account_with_an_unsupported_role(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/users', [
            'name' => 'Compte non autorisé',
            'email' => 'unsupported@example.test',
            'password' => 'A-long-test-password-1',
            'password_confirmation' => 'A-long-test-password-1',
            'role' => 'employee',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'unsupported@example.test']);
    }

    public function test_hr_cannot_access_account_administration(): void
    {
        /** @var User $hr */
        $hr = User::factory()->create(['role' => 'rh']);

        $this->actingAs($hr)->get('/users')->assertForbidden();
        $this->actingAs($hr)->post('/users', [])->assertForbidden();
    }

    public function test_manager_cannot_access_account_administration(): void
    {
        /** @var User $manager */
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)->get('/users')->assertForbidden();
        $this->actingAs($manager)->post('/users', [])->assertForbidden();
    }

    public function test_last_admin_cannot_be_demoted(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/users/'.$admin->id, [
            'name' => $admin->name,
            'email' => $admin->email,
            'password' => '',
            'password_confirmation' => '',
            'role' => 'rh',
            'employee_id' => null,
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
    }

    public function test_admin_cannot_delete_the_current_account(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->delete('/users/'.$admin->id)->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_last_admin_cannot_delete_the_account_from_profile(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrors('account');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
    }
}
