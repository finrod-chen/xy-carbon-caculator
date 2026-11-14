<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 電子郵件不分大小寫（SQLite 的字串比對區分大小寫）。 */
class EmailCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_accepts_uppercase_email(): void
    {
        $user = User::create([
            'name' => '大小寫測試',
            'email' => 'mixed.case@example.com',
            'password' => 'password123456',
            'role' => Role::Contributor,
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $this->post('/login', [
            'email' => 'MIXED.CASE@example.com',
            'password' => 'password123456',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_admin_creating_user_stores_lowercase_email(): void
    {
        $admin = User::create([
            'name' => '管理員',
            'email' => 'admin.case@example.com',
            'password' => 'password123456',
            'role' => Role::Admin,
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $this->actingAs($admin)->post('/admin/users', [
            'name' => '新同事',
            'email' => 'Mixed.Case2@Example.COM',
            'role' => 'contributor',
            'department_id' => null,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'mixed.case2@example.com']);
    }
}
