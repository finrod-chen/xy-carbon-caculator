<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(Role $role, bool $active = true): User
    {
        return User::create([
            'name' => $role->label().'測試',
            'email' => uniqid('role').'@example.com',
            'password' => 'password123456',
            'role' => $role,
            'is_active' => $active,
            'must_change_password' => false,
        ]);
    }

    public function test_contributor_gets_403_on_user_management(): void
    {
        $this->actingAs($this->makeUser(Role::Contributor))
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_admin_can_open_user_management(): void
    {
        $this->actingAs($this->makeUser(Role::Admin))
            ->get('/admin/users')
            ->assertOk();
    }

    public function test_auditor_has_read_only_access(): void
    {
        $auditor = $this->makeUser(Role::Auditor);

        $this->actingAs($auditor)->get('/admin/users')->assertOk();
        $this->actingAs($auditor)->get('/admin/users/create')->assertForbidden();
        $this->actingAs($auditor)->post('/admin/users', [
            'name' => '新使用者',
            'email' => 'new@example.com',
            'role' => Role::Contributor->value,
        ])->assertForbidden();
    }

    public function test_deactivated_user_session_is_rejected(): void
    {
        $user = $this->makeUser(Role::Contributor, false);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_admin_can_create_and_deactivate_a_user(): void
    {
        $admin = $this->makeUser(Role::Admin);
        $target = $this->makeUser(Role::Contributor);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => '新同事',
            'email' => 'colleague@example.com',
            'role' => Role::Reviewer->value,
        ]);

        $this->assertStringContainsString(
            '/admin/users/temp-password/',
            (string) $response->headers->get('Location'),
        );

        $this->assertDatabaseHas('users', [
            'email' => 'colleague@example.com',
            'role' => Role::Reviewer->value,
            'must_change_password' => true,
        ]);

        $this->actingAs($admin)
            ->post('/admin/users/'.$target->id.'/deactivate')
            ->assertRedirect();

        $this->assertFalse($target->fresh()->is_active);
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $admin = $this->makeUser(Role::Admin);

        $this->actingAs($admin)
            ->post('/admin/users/'.$admin->id.'/deactivate')
            ->assertForbidden();
    }
}
