<?php

namespace Tests\Feature;

use App\Support\Content\PageRepository;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 說明頁快取的 HTML 必須用相對網址，否則換網域就整份壞掉。 */
class LearnLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_links_are_relative(): void
    {
        $this->seed(ReferenceSeeder::class);

        $html = app(PageRepository::class)->all()['method']->html;

        $this->assertStringContainsString('/learn/references#R-', $html);
        $this->assertStringNotContainsString('http', $html);
    }
}
