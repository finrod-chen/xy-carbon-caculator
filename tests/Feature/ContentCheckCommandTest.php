<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/** 內容引用檢查：CI 用 content:check 把關，執行期只留提示。 */
class ContentCheckCommandTest extends TestCase
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
            'name' => '內容檢查',
            'email' => uniqid('content').'@example.com',
            'password' => 'password123456',
            'role' => Role::Contributor,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_content_check_passes_for_current_content(): void
    {
        $this->artisan('content:check')->assertSuccessful();
    }

    public function test_content_check_fails_when_a_reference_is_missing(): void
    {
        $path = resource_path('content/__test-check.md');
        file_put_contents($path, "---\ntitle: 缺出處\norder: 901\n---\n\n請見 [R-98]。\n");

        try {
            $this->artisan('content:check')->assertFailed();
        } finally {
            @unlink($path);
        }
    }

    public function test_unknown_reference_renders_as_plain_text_and_logs_a_warning(): void
    {
        $path = resource_path('content/__test-plain.md');
        file_put_contents($path, "---\ntitle: 純文字\norder: 902\n---\n\n請見 [R-97]。\n");

        Log::spy();

        try {
            $this->actingAs($this->user())
                ->get('/learn/__test-plain')
                ->assertOk()
                ->assertSee('[R-97]', false);

            Log::shouldHaveReceived('warning')->atLeast()->once();
        } finally {
            @unlink($path);
        }
    }
}
