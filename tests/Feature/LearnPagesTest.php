<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Support\Content\PageRepository;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class LearnPagesTest extends TestCase
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
            'name' => '說明頁測試',
            'email' => uniqid('learn').'@example.com',
            'password' => 'password123456',
            'role' => Role::Contributor,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_index_lists_all_skeleton_pages(): void
    {
        $response = $this->actingAs($this->user())->get('/learn');

        $response->assertOk()
            ->assertSee('碳盤查總覽')
            ->assertSee('範疇一：直接排放')
            ->assertSee('常見問題');
    }

    public function test_page_shows_reference_links_and_warning_for_unverified_sources(): void
    {
        $response = $this->actingAs($this->user())->get('/learn/method');

        $response->assertOk()
            ->assertSee('方法學與數據品質')
            ->assertSee('內容待廠務確認')
            ->assertSee('R-13', false)
            ->assertSee('尚未核對原文或超過 12 個月未複核');
    }

    public function test_page_returns_404_for_unknown_slug(): void
    {
        $this->actingAs($this->user())->get('/learn/not-a-page')->assertNotFound();
    }

    public function test_references_page_lists_seeded_sources(): void
    {
        $this->actingAs($this->user())
            ->get('/learn/references')
            ->assertOk()
            ->assertSee('R-01')
            ->assertSee('待核對');
    }

    public function test_unknown_reference_code_fails_at_build_time(): void
    {
        $path = resource_path('content/__test-bad-reference.md');
        file_put_contents($path, "---\ntitle: 壞掉的頁面\norder: 999\n---\n\n請見 [R-99]。\n");

        try {
            $this->expectException(RuntimeException::class);
            app(PageRepository::class)->all();
        } finally {
            @unlink($path);
        }
    }

    public function test_raw_html_and_scripts_are_stripped(): void
    {
        $path = resource_path('content/__test-xss.md');
        file_put_contents($path, "---\ntitle: XSS 測試\norder: 998\n---\n\n<script>alert('x')</script>\n\n正常文字 [R-01]\n");

        try {
            $pages = app(PageRepository::class)->all();
            $html = $pages['__test-xss']->html;

            $this->assertStringNotContainsString('<script', $html);
            $this->assertStringContainsString('正常文字', $html);
        } finally {
            @unlink($path);
        }
    }
}
