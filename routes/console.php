<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 每日驗證稽核雜湊鏈（WP0.3）；失敗會在排程紀錄留下痕跡。
Schedule::command('audit:verify')->dailyAt('03:10')->onOneServer();
