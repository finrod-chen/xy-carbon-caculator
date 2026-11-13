<?php

namespace App\Support;

/**
 * 職務分離：同一個人不可審核自己填報的資料（TECH_DESIGN 第 4 節）。
 * 階段 0 先建立並測試，階段 1 的審核流程直接沿用。
 */
final class SeparationOfDuties
{
    public static function canReview(?int $enteredBy, ?int $reviewerId): bool
    {
        if ($enteredBy === null || $reviewerId === null) {
            return false;
        }

        return $enteredBy !== $reviewerId;
    }

    public static function ensure(?int $enteredBy, ?int $reviewerId): void
    {
        abort_unless(self::canReview($enteredBy, $reviewerId), 403, '不可審核自己填報的資料。');
    }
}
