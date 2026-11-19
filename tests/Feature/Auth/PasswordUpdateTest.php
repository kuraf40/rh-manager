<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_cannot_be_updated_directly_without_email_verification(): void
    {
        $user = User::factory()->create(['role' => 'manager']);

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => 'A-new-long-password-4',
            'password_confirmation' => 'A-new-long-password-4',
        ])->assertNotFound();

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }
}
