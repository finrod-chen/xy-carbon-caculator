<?php

namespace App\Providers;

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
        // SQLite 的 WAL、busy_timeout、外鍵設定在 config/database.php 的 sqlite 區塊，
        // 不在這裡連線，避免 artisan 於無資料庫檔時（例如 composer 的 package:discover）失敗。
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
