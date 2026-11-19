<?php

namespace Tests\Feature\Auth;

use App\Mail\UserActionVerificationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')->assertOk();
    }

    public function test_non_admin_does_not_receive_a_password_reset_code(): void
    {
        $user = User::factory()->create(['role' => 'manager']);
        Mail::fake();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('user_action_verifications', ['user_id' => $user->id]);
    }

    public function test_admin_can_reset_password_after_entering_the_email_code(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        Mail::fake();

        $this->post('/forgot-password', ['email' => $administrator->email])
            ->assertRedirect()
            ->assertSessionHas('password_reset_email', $administrator->email);

        $code = null;
        Mail::assertSent(UserActionVerificationCode::class, function (UserActionVerificationCode $mail) use (&$code, $administrator): bool {
            $code = $mail->code;

            return $mail->hasTo($administrator->email) && $mail->action === 'password';
        });

        $this->post('/forgot-password/verify', [
            'email' => $administrator->email,
            'code' => $code,
            'password' => 'A-new-admin-password-3',
            'password_confirmation' => 'A-new-admin-password-3',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('A-new-admin-password-3', $administrator->refresh()->password));
        $this->assertDatabaseMissing('user_action_verifications', ['user_id' => $administrator->id]);
    }
}
