<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => '測試使用者',
            'email' => uniqid('user').'@example.com',
            'password' => 'password123456',
            'role' => Role::Contributor,
            'is_active' => true,
            'must_change_password' => false,
        ], $overrides));
    }

    public function test_registration_routes_are_not_exposed(): void
    {
        $this->assertFalse(Route::has('register'));
        $this->get('/register')->assertNotFound();
    }

    public function test_user_must_change_temporary_password_before_using_the_system(): void
    {
        $user = $this->makeUser(['must_change_password' => true]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123456',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->get('/')->assertRedirect(route('password.edit'));
    }

    public function test_login_locks_after_five_failed_attempts(): void
    {
        $user = $this->makeUser();
        RateLimiter::clear('login:'.$user->email.'|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        // 第六次即使密碼正確也必須被拒絕
        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password123456',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_unknown_email_and_wrong_password_share_the_same_message(): void
    {
        $user = $this->makeUser();

        $this->from('/login')->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'password123456',
        ]);
        $unknownMessage = session('errors')->first('email');

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
        $wrongPasswordMessage = session('errors')->first('email');

        $this->assertSame($unknownMessage, $wrongPasswordMessage);
        $this->assertSame('帳號或密碼不正確。', $unknownMessage);
    }

    public function test_inactive_accounts_cannot_log_in(): void
    {
        $user = $this->makeUser(['is_active' => false]);

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password123456',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_password_change_clears_the_flag(): void
    {
        $user = $this->makeUser(['must_change_password' => true]);

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password123456',
            'password' => 'Vz7#tqwmKpr9LduX',
            'password_confirmation' => 'Vz7#tqwmKpr9LduX',
        ])->assertRedirect(route('home'));

        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_weak_passwords_are_rejected(): void
    {
        $user = $this->makeUser(['must_change_password' => true]);

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password123456',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    }
}
