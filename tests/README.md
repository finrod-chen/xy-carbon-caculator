# tests 目錄

- `Feature/`：功能測試（登入、授權、說明頁、元件展示）。
- `Unit/`：單元測試（職務分離等）。
- 測試使用 SQLite in-memory 與 `RefreshDatabase`；需要參考資料的測試會先執行 `ReferenceSeeder`。
