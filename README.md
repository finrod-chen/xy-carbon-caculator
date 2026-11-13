# 喜躍生醫 內部碳盤查系統

依 [docs/](docs/) 的規劃書實作：ISO 14064-1:2018 組織層級盤查，報表另附 GHG Protocol 範疇對照；減碳目標對齊 SBTi 企業淨零標準 V2.0；永續報告書以 IFRS S1／S2 為主、GRI 輔助。

目前進度：**階段 0（地基）**。實作範圍與驗收標準見 [docs/PHASE0.md](docs/PHASE0.md)。

## 技術版本（WP0.1 核對結果）

| 項目 | 版本 | 說明 |
|---|---|---|
| PHP | 8.3（CI 使用 8.3） | 專案宣告 `^8.2`，與 Laravel 12 相容 |
| Laravel | 12.x | 以 `composer create-project laravel/laravel:^12.0` 建立，骨架由 GitHub Actions 產生（見 `.github/workflows/bootstrap-laravel.yml`），因為規劃環境沒有 composer 執行權限 |
| 資料庫 | SQLite（WAL） | 可切換 MySQL 8；CI 兩者都跑遷移 |
| 前端 | Blade + Vite | Alpine.js 與 Chart.js 於後續工作包加入 |
| 測試 | PHPUnit | 規劃書原定 Pest；骨架預設為 PHPUnit，是否改用 Pest 待確認 |

## 安裝

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

初始管理員由環境變數建立，不寫死在程式或文件中：

```bash
ADMIN_EMAIL=you@example.com ADMIN_NAME="你的名字" php artisan db:seed
```

未提供 `ADMIN_INITIAL_PASSWORD` 時會產生隨機臨時密碼並印在終端機，首次登入需修改密碼。

## 目錄結構

```
app/Enums/           Role、VerificationStatus 等列舉（對應 TECH_DESIGN 第 8 節）
app/Models/          Eloquent 模型（一表一模型）
app/Services/Audit/  AuditWriter：稽核紀錄與雜湊鏈
config/carbon.php    填報時程、捨入規則、上傳限制、登入節流、備份保留
database/migrations/ 階段 0 資料表（TECH_DESIGN 第 12 節）
database/seeders/    部門、參考資料 R-01…R-14、初始管理員
docs/                規劃書、技術設計、介面設計、驗收標準
```

## 階段 0 目前涵蓋

- 專案骨架與 CI（Pint、SQLite 與 MySQL 遷移與測試）
- 資料表：entities、sites、departments、users 擴充、references、page_references、settings、audit_logs（含只增不改 trigger）
- 角色列舉與授權輔助方法、稽核雜湊鏈寫入與驗證
- 種子：5 個部門、參考資料 R-01 至 R-14（狀態如實標示）、初始管理員

尚未實作（依 PHASE0 工作包排序）：登入與帳號流程、版型與元件、說明頁框架、參考資料頁面與後台、備份指令、部署流程。

## 注意事項

- 排放係數、GWP 與 SBTi 參數都必須連到 `references` 的 `reference_id`，缺少出處者不得標為已核准。
- 參考資料目前 6 筆為「二手」、5 筆「待核對」，上線前須替換為原文並填核對日與核對人。
- 所有時間以 UTC 儲存，介面以 Asia/Taipei 顯示。
