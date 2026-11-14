<?php

namespace Tests\Feature;

use App\Support\Content\PageRepository;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 內容與參考資料的把關放在 CI：說明頁只要引用到不存在的 [R-xx]，這裡就紅。
 * 執行時不再丟例外，只顯示「待補」提示，避免單一筆缺漏讓整頁 500。
 */
class ContentReferenceIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_reference_code_used_in_content_exists(): void
    {
        $this->seed(ReferenceSeeder::class);

        $missing = [];

        foreach (app(PageRepository::class)->all() as $page) {
            foreach ($page->missingReferences as $code) {
                $missing[] = $page->slug.' → '.$code;
            }
        }

        $this->assertSame([], $missing, '說明頁引用了不存在的參考資料：'.implode('、', $missing));
    }

    public function test_unknown_code_is_reported_without_breaking_the_page(): void
    {
        $this->seed(ReferenceSeeder::class);
        $path = resource_path('content/__test-missing-ref.md');
        file_put_contents($path, "---\ntitle: 缺出處\norder: 997\n---\n\n請見 [R-98]。\n");

        try {
            $pages = app(PageRepository::class)->all();

            $this->assertSame(['R-98'], $pages['__test-missing-ref']->missingReferences);
        } finally {
            @unlink($path);
        }
    }
}
