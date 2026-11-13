<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** 臨時密碼只顯示一次，且不經過 flash／session。 */
class TemporaryPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => '臨時密碼測試',
            'email' => uniqid('temp').'@example.com',
            'password' => 'password123456',
            'role' => Role::Admin,
            'is_active' => true,
            'must_change_password' => false,
        ], $attributes));
    }

    public function test_created_user_password_page_is_shown_once(): void
    {
        $admin = $this->makeUser();

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => '新同事',
            'email' => 'new.colleague@example.com',
            'role' => 'contributor',
            'department_id' => null,
        ]);

        $location = (string) $response->headers->get('Location');

        $this->assertStringContainsString('/admin/users/temp-password/', $location);
        $this->assertArrayNotHasKey('status', session()->all());

        $this->actingAs($admin)->get($location)->assertOk()->assertSee('臨時密碼');

        // 第二次取用就失效
        $this->actingAs($admin)->get($location)->assertRedirect(route('admin.users.index'));
    }

    public function test_guest_cannot_open_the_temporary_password_page(): void
    {
        $this->get('/admin/users/temp-password/whatever')->assertRedirect(route('login'));
    }

    public function test_reset_password_deletes_that_users_sessions(): void
    {
        config(['session.driver' => 'database']);

        $admin = $this->makeUser();
        $target = $this->makeUser(['email' => 'target@example.com', 'role' => Role::Contributor]);

        DB::table('sessions')->insert([
            'id' => 'target-device',
            'user_id' => $target->id,
            'ip_address' => '10.0.0.5',
            'user_agent' => 'phone',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($admin)
            ->post('/admin/users/'.$target->id.'/reset-password')
            ->assertRedirect();

        $this->assertDatabaseMissing('sessions', ['id' => 'target-device']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'user.password_reset']);
    }
}
