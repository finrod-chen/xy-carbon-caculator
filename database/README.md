# database 目錄

- `migrations/`：階段 0 到階段 6 的資料表，依 TECH_DESIGN 第 12 節的順序建立。所有遷移都必須能在 SQLite 與 MySQL 8 執行。
- `seeders/`：部門、參考資料 R-01…R-14、初始管理員。
- `factories/`：測試用資料。
- `database.sqlite`：本機開發用的資料庫檔，不進版控。建立方式：`touch database/database.sqlite` 後執行 `php artisan migrate`。

SQLite 的 WAL、busy_timeout 與外鍵設定放在 `config/database.php` 的 sqlite 區塊，避免在服務提供者裡連線（否則 `composer install` 的 package:discover 會在沒有資料庫檔時失敗）。
