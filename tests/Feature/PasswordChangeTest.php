<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** 密碼政策（WP0.4）：弱密碼要擋、長度讀設定、改完密碼要留紀錄。 */
class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => '密碼測試',
            'email' => uniqid('pw').'@example.com',
            'password' => 'password123456',
            'role' => Role::Contributor,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_weak_password_is_rejected(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password123456',
            'password' => 'Password1234',
            'password_confirmation' => 'Password1234',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password123456', $user->fresh()->password));
    }

    public function test_minimum_length_comes_from_config(): void
    {
        config(['carbon.auth.password_min_length' => 20]);

        $user = $this->makeUser();

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password123456',
            'password' => 'Vz7#tqwmKpr9LduX',
            'password_confirmation' => 'Vz7#tqwmKpr9LduX',
        ])->assertSessionHasErrors('password');
    }

    public function test_successful_change_updates_password_and_is_audited(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password123456',
            'password' => 'Vz7#tqwmKpr9LduX',
            'password_confirmation' => 'Vz7#tqwmKpr9LduX',
        ])->assertRedirect(route('home'));

        $fresh = $user->fresh();

        $this->assertFalse($fresh->must_change_password);
        $this->assertTrue(Hash::check('Vz7#tqwmKpr9LduX', $fresh->password));
        $this->assertDatabaseHas('audit_logs', ['event' => 'password.changed', 'actor_id' => $user->id]);
    }
}
