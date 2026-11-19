<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_unverified_member_cannot_use_the_old_public_verification_link(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/verify-email/'.$user->id.'/invalid-hash')->assertNotFound();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
