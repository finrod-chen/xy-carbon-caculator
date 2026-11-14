<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 登入安全：換 IP 也繞不過節流、鎖定會落地、不提供記住我。 */
class LoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => '登入測試',
            'email' => uniqid('login').'@example.com',
            'password' => 'password123456',
            'role' => Role::Contributor,
            'is_active' => false,
            'must_change_password' => false,
        ]);
    }

    public function test_failures_from_different_ips_still_lock_the_account(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['is_active' => true])->save();

        for ($i = 1; $i <= 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.'.$i])
                ->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
                ->assertSessionHasErrors('email');
        }

        $this->assertNotNull($user->fresh()->locked_until);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->post('/login', ['email' => $user->email, 'password' => 'password123456'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_unknown_email_gets_the_same_message_as_a_wrong_password(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['is_active' => true])->save();

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'nope-nope-nope'])
            ->assertSessionHasErrors(['email' => '帳號或密碼不正確。']);

        $this->from('/login')
            ->post('/login', ['email' => 'ghost@example.com', 'password' => 'nope-nope-nope'])
            ->assertSessionHasErrors(['email' => '帳號或密碼不正確。']);
    }

    public function test_remember_me_is_not_honoured(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['is_active' => true])->save();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123456',
            'remember' => '1',
        ]);

        $response->assertRedirect();

        foreach ($response->headers->getCookies() as $cookie) {
            $this->assertStringStartsNotWith('remember_web_', $cookie->getName());
        }

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertNull($user->fresh()->remember_token);
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        $user = $this->makeUser();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123456',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
