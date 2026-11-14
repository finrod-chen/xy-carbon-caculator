<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 只信任本機與私有網段的代理：偽造 X-Forwarded-For 不可以改變看到的來源 IP。 */
class ProxyTrustTest extends TestCase
{
    use RefreshDatabase;

    public function test_forwarded_header_from_an_untrusted_source_is_ignored(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->withHeader('X-Forwarded-For', '198.51.100.7')
            ->get('/login');

        $this->assertSame('203.0.113.9', $this->app['request']->ip());
    }

    public function test_forwarded_header_from_a_trusted_proxy_is_used(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeader('X-Forwarded-For', '198.51.100.7')
            ->get('/login');

        $this->assertSame('198.51.100.7', $this->app['request']->ip());
    }
}
