<?php

// 系統層設定：對應 PLAN.md 第 2 節與 PHASE0 WP0.1。
// 這裡只放不隨環境改變的預設值；可調項目走 .env 與 settings 資料表。
return [
    'timezone' => 'Asia/Taipei',
    'locale' => 'zh_TW',

    // 填報時程（UI_DESIGN 5.1 建議預設，可在後台調整）
    'deadlines' => [
        'submit_day' => 10,  // 次月 10 日送審截止
        'review_day' => 15,  // 次月 15 日審核截止
        'lock_day' => 20,    // 次月 20 日鎖定
    ],

    // 數值：資料庫存十進位字串，計算用 brick/math 避免浮點誤差
    'rounding' => [
        'activity_quantity' => 6,
        'co2e_kg' => 8,
        'report_tco2e' => 3,
    ],

    // 上傳限制（TECH_DESIGN 第 10 節）
    'uploads' => [
        'disk' => 'local',
        'max_kb' => 10240,
        'mimes' => ['pdf', 'png', 'jpg', 'jpeg', 'xlsx', 'csv'],
    ],

    // 登入與工作階段（WP0.4）
    'auth' => [
        'session_timeout_minutes' => 120,
        'max_attempts' => 5,
        'lockout_minutes' => 15,
        'password_min_length' => 12,
    ],

    // 稽核（WP0.3）：只增不改，雜湊鏈串接
    'audit' => [
        'hash_algo' => 'sha256',
        'redacted_fields' => ['password', 'remember_token', 'two_factor_secret'],
    ],

    // 備份（WP0.10）
    'backup' => [
        'keep' => 30,
        'path' => 'backups',
    ],

    // 預設 GWP 名稱佔位；數值由 GwpSeeder（階段 1）載入，皆須 reference_id
    'gwp' => [
        'default_set' => env('CARBON_DEFAULT_GWP_SET', 'AR6-100'),
    ],

    'app_version' => env('CARBON_APP_VERSION', '0.1.0-phase0'),
];
