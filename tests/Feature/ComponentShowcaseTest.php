<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 元件展示頁（PHASE0 WP0.6）。 */
class ComponentShowcaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ReferenceSeeder::class);
    }

    private function user(): User
    {
        return User::create([
            'name' => '元件測試',
            'email' => uniqid('ui').'@example.com',
            'password' => 'password123456',
            'role' => Role::Contributor,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_showcase_is_available_in_testing_environment(): void
    {
        $this->actingAs($this->user())
            ->get('/showcase')
            ->assertOk()
            ->assertSee('元件展示')
            ->assertSee('主要按鈕')
            ->assertSee('停用狀態');
    }

    public function test_showcase_is_hidden_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->actingAs($this->user())
            ->get('/showcase')
            ->assertNotFound();
    }

    public function test_components_render_expected_markup(): void
    {
        $response = $this->actingAs($this->user())->get('/showcase');

        $response->assertSee('btn ghost', false)
            ->assertSee('tag off', false)
            ->assertSee('role="alert"', false)
            ->assertSee('還沒有資料');
    }

    public function test_field_component_marks_invalid_inputs(): void
    {
        $this->withViewErrors(['demo_email' => '電子郵件格式不正確。']);

        $this->actingAs($this->user())
            ->get('/showcase')
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('電子郵件格式不正確。');
    }
}
