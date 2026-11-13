<?php

namespace App\Enums;

/**
 * 參考資料的核對狀態（PLAN.md 第 11 節）。
 * 只有 original 才視為已核對原文；secondary 與 pending 在頁面與報告上都要標示。
 */
enum VerificationStatus: string
{
    case Original = 'original';   // 已核對原文
    case Secondary = 'secondary'; // 僅二手資料（新聞、顧問摘要）
    case Pending = 'pending';     // 待核對

    public function label(): string
    {
        return match ($this) {
            self::Original => '原文',
            self::Secondary => '二手',
            self::Pending => '待核對',
        };
    }

    /** 說明頁引用到這筆資料時，是否需要顯示警示條。 */
    public function needsWarning(): bool
    {
        return $this !== self::Original;
    }
}
