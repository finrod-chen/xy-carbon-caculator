<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 系統不可以沒有管理員。 */
class LastAdministratorTest extends TestCase
{
    use RefreshDatabase;

    /** @param  array<string, mixed>  $attributes */
    private function makeUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => '管理員測試',
            'email' => uniqid('admin').'@example.com',
            'password' => 'password123456',
            'role' => Role::Admin,
            'is_active' => true,
            'must_change_password' => false,
        ], $attributes));
    }

    public function test_last_admin_cannot_demote_self(): void
    {
        $admin = $this->makeUser();

        $this->actingAs($admin)->put('/admin/users/'.$admin->id, [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'manager',
            'department_id' => null,
        ])->assertSessionHasErrors('role');

        $this->assertSame(Role::Admin, $admin->fresh()->role);
    }

    public function test_admin_can_be_demoted_when_another_admin_exists(): void
    {
        $first = $this->makeUser();
        $second = $this->makeUser();

        $this->actingAs($first)->put('/admin/users/'.$second->id, [
            'name' => $second->name,
            'email' => $second->email,
            'role' => 'manager',
            'department_id' => null,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertSame(Role::Manager, $second->fresh()->role);
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $admin = $this->makeUser();
        $this->makeUser();

        $this->actingAs($admin)
            ->post('/admin/users/'.$admin->id.'/deactivate')
            ->assertForbidden();

        $this->assertTrue($admin->fresh()->is_active);
    }
}
