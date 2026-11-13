<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AlertComponentTest extends TestCase
{
    public function test_alert_renders_its_content_only_once(): void
    {
        $html = Blade::render('<x-ui.alert tone="error">只出現一次</x-ui.alert>');

        $this->assertSame(1, substr_count($html, '只出現一次'));
        $this->assertSame(1, substr_count($html, 'class="alert error"'));
        $this->assertStringContainsString('role="alert"', $html);
    }

    public function test_ok_alert_uses_status_role(): void
    {
        $html = Blade::render('<x-ui.alert tone="ok">完成</x-ui.alert>');

        $this->assertSame(1, substr_count($html, '完成'));
        $this->assertStringContainsString('role="status"', $html);
    }
}
