<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 部署是 Cloudflare → Tunnel → nginx → PHP，Laravel 只會看到代理。
        // 只信任本機與私有網段，不可以用 '*'，否則偽造 X-Forwarded-For 就能繞過 IP 節流或污染稽核 IP。
        $middleware->trustProxies(
            at: [
                '127.0.0.1',
                '::1',
                '10.0.0.0/8',
                '172.16.0.0/12',
                '192.168.0.0/16',
            ],
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
