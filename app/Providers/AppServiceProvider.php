<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // WP0.2：SQLite 以 WAL、外鍵、busy_timeout 運作
        if (config('database.default') === 'sqlite') {
            $pdo = DB::connection()->getPdo();
            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA busy_timeout = 5000');
            $pdo->exec('PRAGMA synchronous = NORMAL');
            $pdo->exec('PRAGMA foreign_keys = ON');
        }

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
