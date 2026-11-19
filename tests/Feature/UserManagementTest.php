<?php

namespace Tests\Feature;

use App\Mail\UserActionVerificationCode;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_manager_account_without_linking_an_employee(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        Mail::fake();

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
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'user_id' => null]);
        $this->assertDatabaseHas('user_action_verifications', [
            'user_id' => $user->id,
            'action' => 'create',
            'email' => $user->email,
        ]);

        $code = null;
        Mail::assertSent(UserActionVerificationCode::class, function (UserActionVerificationCode $mail) use (&$code, $user): bool {
            $code = $mail->code;

            return $mail->hasTo($user->email);
        });

        $this->actingAs($admin)->post(route('users.verification.verify', $user), [
            'code' => $code,
        ])->assertRedirect(route('users.edit', $user));

        $this->assertNotNull($user->refresh()->email_verified_at);
        $this->assertDatabaseMissing('user_action_verifications', ['user_id' => $user->id]);
    }

    public function test_admin_password_change_requires_the_code_sent_to_the_account_email(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'manager']);
        Mail::fake();

        $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'A-new-long-password-2',
            'password_confirmation' => 'A-new-long-password-2',
            'role' => $user->role,
            'employee_id' => null,
        ])->assertRedirect(route('users.verification', $user));

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
        $storedPassword = DB::table('user_action_verifications')
            ->where('user_id', $user->id)
            ->value('pending_password');
        $this->assertIsString($storedPassword);
        $this->assertStringNotContainsString('A-new-long-password-2', $storedPassword);

        $code = null;
        Mail::assertSent(UserActionVerificationCode::class, function (UserActionVerificationCode $mail) use (&$code, $user): bool {
            $code = $mail->code;

            return $mail->hasTo($user->email) && $mail->action === 'password';
        });

        $this->post(route('users.verification.verify', $user), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseHas('user_action_verifications', [
            'user_id' => $user->id,
            'attempts' => 1,
        ]);
        $this->assertTrue(Hash::check('password', $user->refresh()->password));

        $this->post(route('users.verification.verify', $user), ['code' => $code])
            ->assertRedirect(route('users.edit', $user));

        $this->assertTrue(Hash::check('A-new-long-password-2', $user->refresh()->password));
    }

    public function test_expired_account_code_can_be_resent_and_verified(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        Mail::fake();

        $this->actingAs($admin)->post('/users', [
            'name' => 'Membre test',
            'email' => 'expired@example.test',
            'password' => 'A-long-test-password-1',
            'password_confirmation' => 'A-long-test-password-1',
            'role' => 'manager',
            'employee_id' => null,
        ])->assertRedirect();

        $user = User::query()->where('email', 'expired@example.test')->firstOrFail();
        $oldCode = null;
        Mail::assertSent(UserActionVerificationCode::class, function (UserActionVerificationCode $mail) use (&$oldCode, $user): bool {
            $oldCode = $mail->code;

            return $mail->hasTo($user->email);
        });

        $this->travel(16)->minutes();

        $this->post(route('users.verification.verify', $user), ['code' => $oldCode])
            ->assertSessionHasErrors('code');
        $this->assertNull($user->refresh()->email_verified_at);

        $this->post(route('users.verification.resend', $user))->assertRedirect();

        $sentMessages = 0;
        $newCode = null;
        Mail::assertSent(UserActionVerificationCode::class, function (UserActionVerificationCode $mail) use (&$sentMessages, &$newCode, $user): bool {
            $sentMessages++;
            $newCode = $mail->code;

            return $sentMessages === 2 && $mail->hasTo($user->email);
        });

        $this->post(route('users.verification.verify', $user), ['code' => $newCode])
            ->assertRedirect(route('users.edit', $user));
        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_account_code_stops_accepting_attempts_after_five_failures(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        Mail::fake();

        $this->actingAs($admin)->post('/users', [
            'name' => 'Membre test',
            'email' => 'attempts@example.test',
            'password' => 'A-long-test-password-1',
            'password_confirmation' => 'A-long-test-password-1',
            'role' => 'manager',
            'employee_id' => null,
        ])->assertRedirect();

        $user = User::query()->where('email', 'attempts@example.test')->firstOrFail();
        $code = null;
        Mail::assertSent(UserActionVerificationCode::class, function (UserActionVerificationCode $mail) use (&$code, $user): bool {
            $code = $mail->code;

            return $mail->hasTo($user->email);
        });

        $wrongCode = $code === '000000' ? '000001' : '000000';
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('users.verification.verify', $user), ['code' => $wrongCode])
                ->assertSessionHasErrors('code');
        }

        $this->post(route('users.verification.verify', $user), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseHas('user_action_verifications', [
            'user_id' => $user->id,
            'attempts' => 5,
        ]);
        $this->assertNull($user->refresh()->email_verified_at);
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

    public function test_non_admin_cannot_confirm_account_verification(): void
    {
        /** @var User $manager */
        $manager = User::factory()->create(['role' => 'manager']);
        $user = User::factory()->create(['role' => 'employee']);

        $this->actingAs($manager)->post(route('users.verification.verify', $user), [
            'code' => '123456',
        ])->assertForbidden();
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
