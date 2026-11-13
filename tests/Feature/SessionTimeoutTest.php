<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 閒置超過 2 小時要自動登出。 */
class SessionTimeoutTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => '閒置測試',
            'email' => uniqid('idle').'@example.com',
            'password' => 'password123456',
            'role' => Role::Contributor,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_idle_session_is_logged_out(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->withSession(['last_activity_at' => now()->subMinutes(121)->toIso8601String()])
            ->get('/')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_active_session_stays_logged_in(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->withSession(['last_activity_at' => now()->subMinutes(5)->toIso8601String()])
            ->get('/')
            ->assertOk();
    }
}
