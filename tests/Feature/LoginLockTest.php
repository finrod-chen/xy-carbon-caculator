<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/** 第 5 次失敗要鎖帳號，鎖定期間即使密碼正確也拒絕並顯示剩餘分鐘。 */
class LoginLockTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => '鎖定測試',
            'email' => uniqid('lock').'@example.com',
            'password' => 'password123456',
            'role' => Role::Contributor,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_fifth_failure_locks_the_account(): void
    {
        $user = $this->makeUser();

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->assertNotNull($user->fresh()->locked_until);
    }

    public function test_locked_account_is_rejected_even_with_the_right_password(): void
    {
        $user = $this->makeUser();

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        // 清掉節流，確認擋下來的是 locked_until 而不是 RateLimiter
        RateLimiter::clear('login:email:'.sha1(mb_strtolower($user->email)));
        RateLimiter::clear('login:ip:127.0.0.1');

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password123456',
        ])->assertSessionHasErrors('email');

        $message = (string) session('errors')->first('email');

        $this->assertStringContainsString('已鎖定', $message);
        $this->assertStringContainsString('分鐘', $message);
        $this->assertGuest();
    }
}
