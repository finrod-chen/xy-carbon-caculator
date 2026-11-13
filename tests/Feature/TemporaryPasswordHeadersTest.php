<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 一次性密碼頁不可被任何快取留存。 */
class TemporaryPasswordHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_is_not_cacheable(): void
    {
        $admin = User::create([
            'name' => '管理員',
            'email' => uniqid('admin').'@example.com',
            'password' => 'password123456',
            'role' => Role::Admin,
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => '新同事',
            'email' => 'header.check@example.com',
            'role' => 'contributor',
            'department_id' => null,
        ]);

        $location = (string) $response->headers->get('Location');

        $this->actingAs($admin)
            ->get($location)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Pragma', 'no-cache');
    }
}
