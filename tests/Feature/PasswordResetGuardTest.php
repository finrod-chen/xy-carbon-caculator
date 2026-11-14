<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** 管理員不可以對自己重設密碼（會被登出且看不到臨時密碼）。 */
class PasswordResetGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_reset_own_password(): void
    {
        $admin = User::create([
            'name' => '管理員',
            'email' => uniqid('admin').'@example.com',
            'password' => 'password123456',
            'role' => Role::Admin,
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $this->actingAs($admin)
            ->post('/admin/users/'.$admin->id.'/reset-password')
            ->assertForbidden();

        $this->assertTrue(Hash::check('password123456', $admin->fresh()->password));
        $this->assertAuthenticatedAs($admin);
    }
}
