<?php

namespace Database\Seeders;

use App\Enums\VerificationStatus;
use App\Models\Reference;
use Illuminate\Database\Seeder;

/**
 * 參考資料種子：PLAN.md 第 11 節 R-01 至 R-14。
 * 狀態如實標示：原文／二手／待核對；上線前須把二手與待核對改為原文。
 */
class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['R-01', 'Corporate Net-Zero Standard V2.0', 'SBTi', '2026-06（2027-02-01 生效）', '2026-06-01', 'https://files.sciencebasedtargets.org/production/files/Corporate-Net-Zero-Standard-version-2.pdf', '目標框架、公司類別、基準年、重新計算', VerificationStatus::Original, '2026-11-08', null],
            ['R-02', 'Corporate Net-Zero Standard V2.0 Criteria', 'SBTi', '2026-06', '2026-06-01', 'https://files.sciencebasedtargets.org/production/files/Corporate-Net-Zero-Standard-V2-Criteria.pdf', '逐條準則', VerificationStatus::Pending, null, '已下載，尚未逐條核對'],
            ['R-03', 'V2.0 Criteria for Category B companies', 'SBTi', '2026-06', '2026-06-01', 'https://files.sciencebasedtargets.org/production/files/Corporate-Net-Zero-Standard-V2-Cat-B-Minimum-Criteria.pdf', 'Category B 要求', VerificationStatus::Pending, null, '已下載，尚未逐條核對'],
            ['R-04', 'V2.0 Appendix 1：目標設定方法', 'SBTi', '取得版本標示 2025-03', '2025-03-01', 'https://files.sciencebasedtargets.org/production/files/Documentation-of-target-setting-methods.pdf', '逐年減量速率推算', VerificationStatus::Original, '2026-11-08', '版本待確認，可能非最終版'],
            ['R-05', 'Corporate Net-Zero Standard V1.3.1', 'SBTi', '2026-04', '2026-04-01', 'https://files.sciencebasedtargets.org/production/files/Net-Zero-Standard.pdf', 'V1 並存期間的舊規則（4.2%／2.5% 等）', VerificationStatus::Secondary, null, '僅由二手資料得知'],
            ['R-06', 'Amendments to Greenhouse Gas Emissions Disclosures（IFRS S2 修正）', 'ISSB', '2025-12（2027-01-01 起適用）', '2025-12-01', 'https://www.ifrs.org/projects/completed-projects/2025/amendments-to-disclosure-of-greenhouse-gas-emissions-s2/', 'S2 溫室氣體揭露', VerificationStatus::Original, '2026-11-08', '項目頁已開，修正條文細節待核對'],
            ['R-07', 'IFRS S1、IFRS S2 標準本文', 'ISSB', '2023', '2023-06-26', 'https://www.ifrs.org/issued-standards/ifrs-sustainability-standards-navigator/', '永續與氣候揭露要求', VerificationStatus::Pending, null, null],
            ['R-08', 'GHG Protocol 標準與修訂進度', 'GHG Protocol', '2015 版仍有效', null, 'https://ghgprotocol.org/public-consultations', '盤查方法、範疇二指引修訂進度', VerificationStatus::Secondary, null, '修訂頁面已開；2015 版仍有效屬二手'],
            ['R-09', 'ISO 14064-1:2018', 'ISO', '2018', '2018-12-01', 'https://www.iso.org/', '組織層級盤查與報告', VerificationStatus::Pending, null, null],
            ['R-10', '我國接軌 IFRS 永續揭露準則及政策藍圖（2026-09-23 修正）', '金管會', '2026-09-23 修正', '2026-09-23', 'https://ec.ltn.com.tw/article/breakingnews/5346948', 'IPO 後揭露時程', VerificationStatus::Secondary, null, '僅新聞報導，原始公告尚未取得'],
            ['R-11', '2025 年度公用售電業電力排碳係數（0.467 kgCO₂e/度，產業用 0.466）', '經濟部能源署', '2025 年度', null, 'https://wantrich.chinatimes.com/news/20260602900709-420101', '範疇二計算', VerificationStatus::Secondary, null, null],
            ['R-12', '溫室氣體排放係數公告（113 年 2 月 5 日）', '環境部', '113-02-05', '2024-02-05', 'https://agrinetzero.moa.gov.tw/CarbonFactor/組織盤查溫室氣體排放係數.pdf', '範疇一、三係數', VerificationStatus::Secondary, null, null],
            ['R-13', 'IPCC 評估報告 GWP 值', 'IPCC', 'AR6（100 年）', null, 'https://www.ipcc.ch/', 'CO₂e 換算', VerificationStatus::Pending, null, null],
            ['R-14', 'SBTi V2.0 顧問摘要（交叉比對用）', 'ClimatePartner、UK SRS 等', '2026', null, 'https://www.climatepartner.com/en/knowledge/blog/sbti-net-zero-standard-v2', '交叉比對', VerificationStatus::Secondary, null, '二手，不作為依據'],
        ];

        foreach ($rows as [$code, $title, $publisher, $version, $publishedOn, $url, $purpose, $status, $verifiedOn, $notes]) {
            Reference::updateOrCreate(['code' => $code], [
                'title' => $title,
                'publisher' => $publisher,
                'version_label' => $version,
                'published_on' => $publishedOn,
                'url' => $url,
                'purpose' => $purpose,
                'verification_status' => $status,
                'verified_on' => $verifiedOn,
                'review_due_on' => $verifiedOn ? \Carbon\Carbon::parse($verifiedOn)->addYear() : null,
                'notes' => $notes,
            ]);
        }
    }
}
