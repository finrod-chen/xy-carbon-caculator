# 喜躍生醫 內部碳盤查網站：技術設計（Laravel 專案結構與資料表）

> 狀態：設計稿，尚未開發。對應 [PLAN.md](PLAN.md) 第 6 版。
> 本文件不含程式碼，只定義結構、欄位、規則與測試策略。標準與係數的出處與核對狀態以 PLAN.md 第 11 節為準。

## 1. 設計原則

1. **可追溯**：任何一個對外數字都能回溯到活動數據、係數、GWP、出處與經手人。
2. **凍結計算結果**：活動資料存檔或送審時，凍結當下的係數、GWP 與計算結果；係數更新不影響已核准資料。
3. **狀態機明確**：資料、期間、報告、目標都有明確狀態與合法轉換，非法轉換在程式與資料庫層雙重擋下。
4. **資料庫可移轉**：用 SQLite 起步，但不依賴 SQLite 專屬語法，每個遷移檔都須能在 MySQL 8 執行（CI 雙跑驗證）。
5. **設定而非寫死**：SBTi 參數、異常門檻、提醒時程、單位換算都放資料表或設定，不寫進程式。
6. **簡單優先**：單一場址、50 人規模，用 Laravel 內建能力，不引入微服務、佇列叢集或搜尋引擎。

## 2. 技術選型

| 項目 | 選擇 | 備註 |
|---|---|---|
| 語言 | PHP 8.3 | 環境已有 8.3.6 |
| 框架 | Laravel（初始化時採用當時穩定版） | 版本號於建立專案時核對，不在規劃階段預設 |
| 資料庫 | SQLite（WAL） | 路徑放在網站根目錄之外；日後可換 MySQL 8 |
| 前端 | Blade + Alpine.js + Tailwind CSS（Vite 建置） | 無 SPA；建置產物可提交或於部署時建置 |
| 圖表 | Chart.js（npm 安裝後由 Vite 打包） | 不使用 CDN，內網可用 |
| 認證 | Laravel Breeze（Blade 版）精簡後使用 | 移除公開註冊，帳號由管理員建立 |
| 授權 | Policy + Gate，角色用欄位與列舉 | 不引入權限套件，規模不需要 |
| 數值運算 | bcmath（或 brick/math） | 避免浮點誤差，見 5.10 |
| Excel | phpoffice/phpspreadsheet | 匯入與匯出 |
| PDF | mPDF（內嵌 Noto Sans TC） | 中文字型支援較穩；候選為 Dompdf，動工時以實測選定 |
| Word | phpoffice/phpword | 永續報告氣候章節 |
| Markdown | league/commonmark | 說明頁內容渲染 |
| 測試 | Pest | 單元、功能測試 |
| 靜態分析與格式 | Larastan、Laravel Pint | 納入 CI |
| 排程與佇列 | Laravel Scheduler + database queue | 單機，不需 Redis |
| 部署 | Docker Compose（php-fpm + nginx） | 另附可選的 MySQL profile 供日後遷移 |

## 3. 專案目錄結構

```
app/
├── Enums/                     # 狀態與類型列舉（見第 8 節）
├── Models/                    # Eloquent 模型，一表一模型
├── Http/
│   ├── Controllers/
│   │   ├── Learn/             # 說明頁、參考資料頁（公開給登入者）
│   │   ├── Entry/             # 填報、批次匯入
│   │   ├── Review/            # 審核佇列
│   │   ├── Dashboard/         # 主管 Dashboard
│   │   ├── Targets/           # 目標、措施、SBTi 檢核
│   │   ├── Disclosure/        # IFRS S2 登錄、績效指標
│   │   ├── Reports/           # 報表與版本
│   │   └── Admin/             # 使用者、排放源、係數、設定
│   ├── Requests/              # 表單驗證
│   ├── Middleware/            # 角色、期間鎖定、稽核
│   └── ViewModels/            # 頁面所需的組裝資料
├── Policies/                  # 各模型授權
├── Actions/                   # 單一動作：SubmitRecord、ApproveRecord、LockPeriod…
├── Services/
│   ├── Emissions/             # EmissionCalculator、FactorResolver、UnitConverter、Snapshotter
│   ├── Scope3/                # ScreeningService、SpendEstimator
│   ├── Sbti/                  # CriteriaRepository、CategoryClassifier、PathwayCalculator、CriteriaChecker
│   ├── Targets/               # MilestoneBuilder、ProgressService、RecalculationEvaluator
│   ├── Anomaly/               # 異常規則與評估
│   ├── Dashboard/             # 完整度矩陣、KPI 查詢
│   ├── Reports/               # ExcelExporter、PdfBuilder、DocxBuilder、SnapshotBuilder、AssurancePack
│   └── Audit/                 # AuditWriter、HashChain
├── Observers/                 # 模型事件寫入稽核紀錄
├── Jobs/                      # 匯入、報表產生、寄信
├── Notifications/             # 提醒信
├── Console/Commands/          # 備份、提醒、核對過期掃描
└── Support/                   # Decimal 工具、日期期間工具
config/
├── carbon.php                 # 本系統設定：預設 GWP、捨入規則、提醒時程、上傳限制
database/
├── migrations/                # 依階段拆分
├── seeders/                   # 見第 9 節
└── data/                      # 種子資料 CSV／JSON（含出處欄位）
resources/
├── content/                   # 說明頁 Markdown（含 front matter 與 [R-xx] 引用）
│   ├── overview/ scope1/ scope2/ scope3/ method/ sop/ faq/ glossary/
├── views/                     # Blade：layouts、components、各模組
├── reports/                   # 報告範本（docx 範本、pdf blade、excel 版面）
└── css js                     # Tailwind、Alpine、Chart.js 進入點
routes/
├── web.php                    # 依模組分群載入
storage/app/private/           # 佐證檔、報表輸出（非公開）
tests/
├── Unit/ Feature/ Fixtures/   # 含計算黃金案例
docker/ docs/
```

模組邊界規則：Controller 只處理請求與回應；業務規則在 `Actions` 與 `Services`；Model 不放跨表流程。

## 4. 角色與授權

| 角色 | 填報 | 審核 | 看 Dashboard | 目標與措施 | 報告 | 後台 |
|---|---|---|---|---|---|---|
| contributor（填報者） | 自己部門的排放源 | 否 | 否 | 唯讀 | 否 | 否 |
| dept_head（單位主管） | 自己部門 | 可退回或核准自己部門 | 部門視圖 | 唯讀 | 否 | 否 |
| reviewer（審核者） | 否 | 全部 | 是 | 唯讀 | 草稿 | 否 |
| manager（管理者） | 否 | 全部 | 是 | 核准目標、發布 | 核准發布 | 否 |
| esg（永續／ESG 窗口） | 否 | 否 | 是 | 編輯措施與風險登錄 | 編輯 | 否 |
| admin（系統管理員） | 否 | 否 | 是 | 編輯 | 是 | 全部 |
| auditor（稽核唯讀） | 否 | 否 | 是 | 唯讀 | 唯讀 | 唯讀 |

規則：
- **職務分離**：同一個人不可核准自己填的資料（程式檢查 `entered_by != reviewer`）。
- 部門視圖由 `users.department_id` 與 `emission_sources.department_id` 對應。
- 一位使用者一個主要角色；若需兼任，建立 `role_user` 多對多表（目前不做，預留）。
- 已鎖定期間的任何寫入一律拒絕，只能透過解鎖申請流程。

## 5. 資料庫設計

所有表預設含 `id`（整數主鍵）、`created_at`、`updated_at`。標 *SD* 表示軟刪除（`deleted_at`）。時間戳以 UTC 儲存、介面以 Asia/Taipei 顯示。外鍵一律啟用並設定刪除規則（預設 restrict）。

### 5.1 組織與帳號

| 表 | 欄位 | 說明 |
|---|---|---|
| `entities` | name, legal_name, tax_id?, boundary_approach(列舉), country, fiscal_year_start_month(預設 1), is_active | 報告個體；目前 1 筆，為 IPO 後新增子公司預留 |
| `sites` | entity_id→, name, address?, floor_area_m2?, notes | 場址；目前 1 筆 |
| `departments` | name, code(unique), is_active | 行政、採購、財務、研發、人資、管理 |
| `users` | name, email(unique), password, role(列舉), department_id?→, is_active, last_login_at?, remember_token | 無公開註冊；管理員建立；停用而非刪除 |

### 5.2 盤查年度與期間

| 表 | 欄位 | 說明 |
|---|---|---|
| `inventory_years` | entity_id→, year, status(open/in_review/locked/published), is_inventory_base_year, base_year_note?, boundary_snapshot(json)?, locked_at?, locked_by?→ | unique(entity_id, year)；2025 為盤查基準年 |
| `reporting_periods` | inventory_year_id→, month(1–12), status(open/submitted/locked), locked_at?, locked_by?→ | unique(inventory_year_id, month)；建年度時自動建立 12 筆 |
| `period_unlock_requests` | reporting_period_id→, requested_by→, reason, status(pending/approved/rejected), decided_by?→, decided_at?, decision_note? | 解鎖須理由與核准，兩者皆寫稽核 |

### 5.3 目錄、係數與出處

| 表 | 欄位 | 說明 |
|---|---|---|
| `references` | code(unique, 如 R-01), title, publisher, version_label?, published_on?, url?, purpose, verification_status(original/secondary/pending), verified_on?, verified_by?→, review_due_on?, notes? | 對應參考資料頁；排放係數等都指向此表 |
| `page_references` | page_slug, reference_id→ | 說明頁引用了哪些出處；unique(page_slug, reference_id) |
| `gwp_sets` | name, assessment(AR5/AR6…), horizon_years(預設 100), is_default, reference_id→ | 預設採最新 IPCC 版本 |
| `gwp_values` | gwp_set_id→, gas_code, value(decimal 12,3) | unique(gwp_set_id, gas_code)；含 CO2、CH4、N2O、SF6、NF3、各 HFC／PFC 與冷媒混合物 |
| `emission_sources` | code(unique), name_zh, name_en?, scope(1/2/3), iso_category(1–6), ghg_scope3_category?(1–15), source_type(列舉), calc_method(activity/spend/refrigerant_charge/…), default_unit, entry_frequency(monthly/annual), department_id→, requires_evidence, guidance_md?, sort_order, is_active | 排放源目錄；`iso_category` 與 `ghg_scope3_category` 的對照預先載入並標明待核對 |
| `emission_factors` | emission_source_id→, code, name, factor_type(per_gas/co2e/gwp), activity_unit, co2e_per_unit(decimal 24,10)?, energy_gj_per_unit(decimal 18,8)?, biogenic_co2_per_unit?, uncertainty_pct?, applicable_year?, valid_from?, valid_to?, region(預設 TW), gwp_set_id?→, status(draft/verified/active/retired), verified_by?→, verified_at?, reference_id→(必填), notes? | 同一來源可有多筆係數（燃料別、冷媒別、年度別）；`status=active` 才可選用；沒有 `reference_id` 不得為 verified |
| `emission_factor_gases` | emission_factor_id→, gas_code, amount_per_unit(decimal 24,12) | 逐氣體係數（per_gas 類型）：每活動單位的氣體質量 kg；unique(factor, gas) |
| `unit_conversions` | from_unit, to_unit, multiplier(decimal 24,12), note?, reference_id? | unique(from_unit, to_unit)；如 kWh→GJ |
| `fx_rates` | year, currency, rate_to_twd(decimal 18,8), reference_id→ | 支出法使用 |

說明：
- `co2e_per_unit` 是依 GWP 預先計算的「便利值」，僅用於顯示；計算以逐氣體資料或 GWP 為準，並在匯入時比對。
- 冷媒逸散類型（`gwp`）：排放量 = 逸散質量 × 該冷媒的 GWP，無需逐氣體係數。
- 支出法類型（`co2e`）：每新台幣千元的 kgCO₂e，係數可能來自國外 EEIO，須標示粗估。

### 5.4 活動資料與佐證

| 表 | 欄位 | 說明 |
|---|---|---|
| `activity_records` *SD* | billing_period_start?, billing_period_end?, allocation_method?(none/by_days), inventory_year_id→, reporting_period_id→, emission_source_id→, emission_factor_id→, department_id→, entered_by→, quantity(decimal 24,6), unit, quantity_factor_unit(decimal 24,6), data_quality(measured/invoiced/calculated/estimated/spend_based), is_estimate, estimation_method?, vendor_or_note?, scope2_method?(location/market), status(draft/submitted/returned/approved/locked), submitted_at?, reviewed_by?→, reviewed_at?, review_note?, locked_at?, version(預設 1), supersedes_id?→(自己), 以及凍結欄位（見下） | 一筆代表一張帳單或一次數據；同月同來源可多筆 |
| `activity_records` 凍結欄位 | calc_factor_value, calc_factor_label, calc_factor_reference_code, calc_gwp_set_id, calc_gwp_label, calc_co2e_kg(decimal 28,8), calc_co2e_market_kg?, calc_biogenic_co2_kg?, calc_energy_gj?, calc_snapshot(json), calculated_at | 送審時寫入並計算；`calc_snapshot` 存十進位字串，為稽核依據 |
| `attachments` *SD* | attachable_type, attachable_id, disk, path, original_name, mime, size_bytes, sha256, description?, uploaded_by→ | 多型；佐證檔、匯入檔、報告檔；以雜湊驗證完整性 |
| `review_events` | record_id→, user_id→, action(submit/return/approve/unlock/comment), note?, created_at | 審核動作與意見，一筆資料的流程歷史 |
| `anomalies` | activity_record_id→, rule_code, severity(info/warn/high), message, resolved_by?→, resolved_at?, resolution_note? | 異常規則評估結果 |
| `import_batches` | user_id→, attachment_id→, kind, status(pending/done/failed), rows_total, rows_ok, rows_error, error_report(json)? | 批次匯入紀錄 |

索引：`activity_records(inventory_year_id, reporting_period_id, emission_source_id, status)`、`(department_id, status)`、`(emission_factor_id)`。

資料庫層守則：
- `CHECK(quantity >= 0)`，負值須走更正流程。
- `CHECK(status IN (...))`（遷移以列舉值產生，SQLite 與 MySQL 都支援）。
- 已 `locked` 的紀錄以應用層與稽核雙重保護；SQLite 無法可靠用觸發器擋，但加上觸發器作為第二道防線並由測試驗證。

### 5.5 範疇三

| 表 | 欄位 | 說明 |
|---|---|---|
| `scope3_screening` | inventory_year_id→, category(1–15), relevance(high/medium/low/none), estimated_t(decimal 24,6)?, estimation_method(spend/average/activity/none), basis_note, share_pct?, included_in_collection, exclusion_reason?, assessed_by→, assessed_at, reference_id? | unique(year, category)；15 類皆須有記錄，才能判斷占比與涵蓋 |
| `scope3_category_map` | ghg_category, iso_category, note?, reference_id? | GHG Protocol 15 類與 ISO 類別 3–6 的對照，初始資料標為待核對 |

### 5.6 績效指標與強度分母

| 表 | 欄位 | 說明 |
|---|---|---|
| `performance_indicators` | code(unique), name, unit, kind(energy/water/waste/denominator), department_id→, frequency, is_active | 能源、用水、廢棄物、員工人數、樓地板面積、營收 |
| `indicator_values` | indicator_id→, inventory_year_id→, month?, value(decimal 24,6), status(draft/submitted/approved/locked), entered_by→, reviewed_by?→, note? | unique(indicator, year, month) |

### 5.7 減碳目標與措施（SBTi）

| 表 | 欄位 | 說明 |
|---|---|---|
| `sbti_criteria_versions` | label(如 V1.3.1、V2.0), effective_from, effective_to?, status(active/superseded/draft), reference_id→, notes? | 兩版並存；V2.0 自 2027-02-01 |
| `sbti_criteria_params` | version_id→, code(如 CNZS-C8.3), name, value(json), unit?, reference_id→, notes? | 5% 重算門檻、五年期、類別門檻等；unique(version, code) |
| `company_classifications` | entity_id→, determined_on, fte(decimal 10,2), net_turnover_eur?, balance_sheet_eur?, scope12_tco2e?, country_income_group, result(A/B), basis(json), evidence_attachment_id?→, criteria_version_id→, approved_by?→ | 類別判定紀錄；輸入數字由財務提供 |
| `targets` | entity_id→, name, scope_boundary(scope1/scope2/scope3/net_zero…), metric_type(absolute/intensity/lce_alignment/supplier_engagement), inventory_base_year, target_base_year, base_year_emissions_t(decimal 24,6), target_start_on, target_end_on, reduction_pct(decimal 7,3), residual_pct?, method, larr_pct?, coverage_pct, criteria_version_id→, status(draft/approved/registered/submitted/validated/expired), approved_by?→, approved_at?, sbti_ref_no?, notes? | 目標基準年與盤查基準年分開存放 |
| `target_versions` | target_id→, version_no, snapshot(json), changed_by→, reason | 每次修改保留歷史 |
| `target_milestones` | target_id→, year, planned_emissions_t, source(auto/manual), note? | 逐年路徑；unique(target, year) |
| `recalculation_log` | entity_id→, target_id?→, trigger_type(boundary/consolidation/scope_shift/method/error), trigger_on, description, delta_s1_pct?, delta_s2_pct?, delta_s3_pct?, threshold_met, old_base_t?, new_base_t?, decided_by?→, decided_at?, sbti_review_status? | 5% 門檻評估與結果 |
| `sbti_checks` | target_id→, criteria_version_id→, run_by→, run_at, results(json), passed_count, failed_count | 檢核頁每次執行的快照 |
| `reduction_initiatives` *SD* | title, description?, department_id→, owner_id→, emission_source_id?→, scope, status(idea/planned/in_progress/completed/cancelled), start_on?, end_on?, capex_twd?, opex_twd?, expected_reduction_t_per_year?, expected_from_year?, methodology_note?, approved_by?→ | 減碳措施 |
| `initiative_results` | initiative_id→, year, planned_t, actual_t?, verification_note?, attachment_id?→ | 預估與實際並列；unique(initiative, year) |
| `carbon_credits` | vintage, registry, project, quantity_t, retired_on?, purpose, counts_toward_target(固定 false), reference_id? | 碳權獨立記錄，不計入減量進度 |
| `scenarios` | name, params(json), result(json), created_by→ | 情境試算器的儲存結果 |

### 5.8 IFRS S2 與揭露

| 表 | 欄位 | 說明 |
|---|---|---|
| `climate_risks` | kind(physical_acute/physical_chronic/transition/opportunity), title, description, time_horizon(short/medium/long), likelihood?, magnitude?, financial_effect_note?, response?, owner_department_id→, status, assessed_on | 氣候風險與機會登錄 |
| `climate_scenarios` | name, framework, temperature_c?, time_horizon, assumptions, result_note?, assessed_on, reference_id? | 情境分析紀錄 |
| `governance_records` | topic, body(board/committee/management), description, review_frequency?, last_reviewed_on?, evidence_attachment_id?→ | 治理 |
| `internal_carbon_prices` | year, twd_per_tco2e, usage_note? | 內部碳價（如有） |
| `disclosure_items` | framework(IFRS_S2/GRI), code, title, source_route?, status(complete/partial/missing), owner_id?→, note?, reference_id? | 披露項目與資料來源對照 |

### 5.9 報告、稽核與系統

| 表 | 欄位 | 說明 |
|---|---|---|
| `report_versions` | type(iso14064/sustainability_climate/sbti_package/assurance_pack), inventory_year_id→, version_no, status(draft/approved/published), snapshot_attachment_id→, snapshot_sha256, created_by→, approved_by?→, approved_at?, note? | 引用的數字來自凍結快照；任何修改產生新版本 |
| `audit_logs` | user_id?→, auditable_type?, auditable_id?, event(created/updated/deleted/status_changed/login/export/unlock…), old_values(json)?, new_values(json)?, ip?, user_agent?, prev_hash?, row_hash, created_at | 只增不改；雜湊鏈提供防竄改證據 |
| `settings` | key(unique), value(json), type, updated_by?→ | 異常門檻、提醒時程（送審截止日、審核截止日、鎖定日、提醒天數）、捨入規則等 |
| Laravel 內建 | `sessions`、`cache`、`jobs`、`failed_jobs`、`password_reset_tokens`、`notifications` | 使用資料庫驅動 |

### 5.10 SQLite 設定重點

- 連線啟用外鍵；`journal_mode=WAL`、`busy_timeout` 約 5 秒、`synchronous=NORMAL`（依所用 Laravel 版本的設定方式為準）。
- 資料庫檔與備份放在網站根目錄之外，權限僅限應用程式帳號。
- **小數精度**：SQLite 沒有真正的定點小數，宣告 decimal 的欄位實際以浮點儲存。因此：
  1. 所有計算在 PHP 以 bcmath 進行；
  2. 凍結用的 `calc_snapshot` JSON 存十進位字串（不受浮點影響），為稽核依據；
  3. 欄位值用於查詢與彙總，彙總結果與快照比對由測試驗證；
  4. 遷移到 MySQL 後 decimal 為精確儲存，不需要改程式。
- 備份：每日以 SQLite 的線上備份機制產生一致性副本，連同 `storage/app/private` 打包保留 30 天，每月做一次還原演練。

## 6. 核心規則

### 6.1 活動資料狀態機

```
draft ──submit──▶ submitted ──approve──▶ approved ──(期間鎖定)──▶ locked
  ▲                   │
  └────── return ─────┘            locked ──(解鎖申請核准)──▶ approved（寫入新版本，舊版保留）
```

- `submit`：驗證必填與單位，計算並凍結，產生異常檢查。
- `return`：審核者必填意見，狀態回 draft，凍結值清除。
- `approve`：審核者非填報者；寫入 `reviewed_by`。
- 期間鎖定：當月所有紀錄為 approved 才可鎖；鎖定後禁止寫入。
- 更正已鎖定資料：走解鎖申請，核准後建立新版本（`supersedes_id`），舊版保留供稽核。

### 6.2 計算規格

對一筆紀錄，先將數量換算為係數單位 `quantity_factor_unit`，再依係數類型：

- **per_gas**：對每個氣體，`質量 = 數量 × amount_per_unit`，`co2e = 質量 × GWP(氣體)`，加總為 `calc_co2e_kg`。
- **co2e**：`calc_co2e_kg = 數量 × co2e_per_unit`。
- **gwp**（冷媒逸散）：`calc_co2e_kg = 逸散質量(kg) × GWP(冷媒)`。
- **範疇二**：位置基準用電網係數，結果存 `calc_co2e_kg`；`calc_co2e_market_kg` 預設留空，有綠電或合約工具時才填。
- **能源**：`calc_energy_gj = 數量 × energy_gj_per_unit`（有定義時）。
- **生質來源 CO₂**：另存 `calc_biogenic_co2_kg`，不併入總量。
- **捨入**：中間運算保留 12 位小數，最終結果四捨五入至 8 位小數（kg）；報表顯示 tCO₂e 取小數 3 位。規則寫在 `config/carbon.php`，不得散落各處。
- **GWP**：預設用 `gwp_sets.is_default`，計算時存入凍結欄位；換 GWP 版本須走「全年重算」作業並產生重算紀錄。

### 6.3 異常規則（預設，門檻放 `settings`）

| 代碼 | 規則 |
|---|---|
| A01 | 與上月同來源相比變動超過設定百分比 |
| A02 | 與去年同月相比變動超過設定百分比（有資料時） |
| A03 | 數量為 0 或空白但來源要求每月填報 |
| A04 | 單位與來源預設單位不一致且無換算 |
| A05 | 要求佐證但未上傳 |
| A06 | 標為估算但未填估算方法 |
| A07 | 使用 draft 或 retired 的係數 |
| A08 | 同月同來源數量超過歷史最大值一定倍數 |

### 6.4 Dashboard 查詢口徑

- **完整度矩陣**：預期格 = 啟用的排放源（月頻）× 該年 12 個月；格狀態 = 缺資料 / 草稿 / 已送審 / 已核准 / 已鎖定，取該格所有紀錄中最低狀態。
- **KPI**：僅計 `approved` 與 `locked`；草稿與送審另列「待確認」。
- **估算占比**：`is_estimate` 的排放量占總量比例。
- **目標進度**：實際 vs `target_milestones`，差距以 tCO₂e 與百分比表示。
- 重查詢以彙總查詢為主，快取鍵含年度與資料版本，狀態異動時失效。

## 7. 路由與頁面對照

| 路徑 | 說明 |
|---|---|
| `/learn/*` | 說明頁（Markdown 渲染） |
| `/references` | 參考資料頁 |
| `/entry` `/entry/{year}/{month}` | 填報首頁與月份填報 |
| `/entry/import` | 批次匯入 |
| `/review` | 審核佇列 |
| `/dashboard` | 主管 Dashboard |
| `/targets` `/targets/{id}` `/initiatives` | 目標與措施 |
| `/disclosure/*` | 風險登錄、情境、治理、披露對照 |
| `/reports` | 報表與版本 |
| `/admin/*` | 使用者、排放源、係數、GWP、出處、設定、稽核紀錄 |

## 8. 列舉清單

`Role`、`RecordStatus`、`PeriodStatus`、`YearStatus`、`Scope`、`SourceType`、`CalcMethod`、`FactorType`、`FactorStatus`、`DataQuality`、`VerificationStatus`、`TargetStatus`、`MetricType`、`InitiativeStatus`、`RiskKind`、`ReportType`、`ReportStatus`、`BoundaryApproach`。列舉值與遷移中的 CHECK 約束由同一份定義產生，避免兩邊不一致。

## 9. 種子資料

| 種子 | 內容 | 驗證要求 |
|---|---|---|
| `DepartmentSeeder` | 行政總務、採購財務、研發實驗室、人資、管理 | 與 PLAN.md 第 5 節一致 |
| `ReferenceSeeder` | PLAN.md 第 11 節的 R-01 至 R-14 | 狀態如實標示 |
| `GwpSeeder` | 目前最新 IPCC 100 年值與常見冷媒 | 對照原始報告核對 |
| `EmissionSourceSeeder` | 範疇一、二、三來源目錄，含生技業特有項目 | 與部門分派一致 |
| `FactorSeeder` | 由 `database/data/factors.csv` 載入，每列含出處與年度 | 預設狀態 `draft`，核對後才可改 `verified` 與 `active` |
| `UnitConversionSeeder` | 常用單位換算 | 單元測試覆蓋 |
| `SbtiCriteriaSeeder` | V1.3.1 與 V2.0 參數，條號與出處完整 | 以原文逐條核對 |
| `Scope3CategorySeeder` | 15 類與 ISO 對照（待核對） | 狀態標示 |
| `IndicatorSeeder` | 能源、用水、廢棄物、員工人數、樓地板面積 | |
| `DisclosureItemSeeder` | IFRS S2 與 GRI 302／305 項目清單 | 以標準原文核對 |
| `DemoSeeder` | 測試與展示用假資料，僅 local 環境可執行 | 嚴禁在正式環境執行 |

係數 CSV 欄位：`source_code, name, factor_type, activity_unit, co2e_per_unit, gas_breakdown, energy_gj_per_unit, applicable_year, region, reference_code, uncertainty_pct, notes`。

## 10. 安全與營運

- 密碼雜湊、登入節流、強制 HTTPS、安全標頭（含 CSP）；Session 逾時 2 小時；管理員與審核者建議啟用雙因素（第二階段評估）。
- 上傳：檔案放私有磁碟；允許 pdf、png、jpg、xlsx、csv；單檔上限預設 10 MB；檔名改為隨機，保留原名於資料庫；記錄 SHA-256。
- 下載佐證檔經授權檢查後以串流提供，不暴露實體路徑。
- 稽核紀錄為只增；以雜湊鏈串接，每日一次完整性檢查，異常時通知管理員。
- 個資最小化：只存姓名與公司信箱。
- 例行排程：每日備份、截止前提醒信、每週掃描過期出處與草稿係數、每月稽核鏈檢查。

## 11. 測試策略

| 類型 | 內容 |
|---|---|
| 單元 | 計算器黃金案例（手算對照）：燃料、電力、冷媒、支出法；單位換算；捨入；SBTi 類別判定與參數；路徑計算；重算門檻 |
| 功能 | 狀態機所有合法與非法轉換；職務分離；鎖定期間禁寫；解鎖與新版本；各角色授權矩陣；匯入驗證；快照不可變 |
| 一致性 | 報表合計等於活動資料合計；Dashboard KPI 等於報表；盤查報告與永續報告引用同一快照 |
| 相容 | CI 同時在 SQLite 與 MySQL 8 執行遷移與功能測試 |
| 安全 | 越權存取、上傳惡意檔、SQL 注入與 XSS 基本測試 |
| 驗收 | 每階段完成後以展示資料走完整流程，並由使用者確認 |

CI（GitHub Actions）：Pint、Larastan、Pest（SQLite）、Pest（MySQL 服務容器）。

## 12. 遷移順序（對應開發階段）

| 階段 | 遷移內容 |
|---|---|
| 0 | entities、sites、departments、users、sessions、cache、jobs、audit_logs、settings、references、page_references |
| 1 | inventory_years、reporting_periods、period_unlock_requests、gwp_sets、gwp_values、emission_sources、emission_factors、emission_factor_gases、unit_conversions、fx_rates、activity_records、attachments、review_events、anomalies |
| 2 | scope3_screening、scope3_category_map、import_batches（若提早）|
| 3 | performance_indicators、indicator_values（Dashboard 需要強度分母） |
| 4 | report_versions |
| 5 | sbti_criteria_versions、sbti_criteria_params、company_classifications、targets、target_versions、target_milestones、recalculation_log、sbti_checks、reduction_initiatives、initiative_results、carbon_credits、scenarios |
| 6 | climate_risks、climate_scenarios、governance_records、internal_carbon_prices、disclosure_items |

## 13. 待確認事項

0. **計費期間分攤**：帳單可能是雙月或跨月計費期。`activity_records` 新增計費起訖日與分攤方式，採「按天分攤」時由系統拆成各月份的衍生紀錄（衍生紀錄不可獨立修改，只能改原始帳單）。實際是否需要，待廠務確認帳單類型。
1. Laravel 與相依套件版本，於專案初始化時核對最新穩定版與 PHP 8.3 相容性。
2. mPDF 與 Dompdf 的中文排版實測後選定。
3. 係數 CSV 的第一版內容由誰負責整理與核對（對應 PLAN.md 的係數維護者）。
4. 是否需要多角色兼任（目前一人一角色）。
5. 雙因素驗證是否納入第一版。
6. 正式環境主機與備份存放位置。
