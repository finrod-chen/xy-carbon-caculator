<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 改密碼後，其他裝置（session 裡還是舊的密碼雜湊）必須被踢回登入頁。 */
class PasswordSessionTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => '多裝置測試',
            'email' => uniqid('sessions').'@example.com',
            'password' => 'password123456',
            'role' => Role::Contributor,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_other_device_with_stale_password_hash_is_logged_out(): void
    {
        $user = $this->makeUser();

        // 另一個裝置的 session：仍是改密碼前的雜湊
        $this->actingAs($user)
            ->withSession(['password_hash_web' => 'stale-password-hash'])
            ->get('/')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_current_device_keeps_working_after_change(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password123456',
            'password' => 'Vz7#tqwmKpr9LduX',
            'password_confirmation' => 'Vz7#tqwmKpr9LduX',
        ])->assertRedirect(route('home'));

        $this->get('/')->assertOk();
        $this->assertAuthenticatedAs($user->fresh());
    }
}
