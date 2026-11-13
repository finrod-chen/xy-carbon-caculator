<?php

namespace App\Enums;

/**
 * 系統角色（TECH_DESIGN 第 4 節）。一位使用者一個主要角色；多角色兼任預留 role_user 表，目前不做。
 */
enum Role: string
{
    case Contributor = 'contributor'; // 填報者：自己部門的排放源
    case DeptHead = 'dept_head';     // 單位主管：核准或退回自己部門
    case Reviewer = 'reviewer';      // 審核者：全部資料
    case Manager = 'manager';        // 管理者：核准目標、發布報告
    case Esg = 'esg';                // 永續窗口：措施與風險登錄、報告編輯
    case Admin = 'admin';            // 系統管理員
    case Auditor = 'auditor';        // 稽核唯讀

    public function label(): string
    {
        return match ($this) {
            self::Contributor => '填報者',
            self::DeptHead => '單位主管',
            self::Reviewer => '審核者',
            self::Manager => '管理者',
            self::Esg => '永續窗口',
            self::Admin => '系統管理員',
            self::Auditor => '稽核唯讀',
        };
    }

    /** 後台（使用者、部門、設定、出處維護）只給管理員。 */
    public function canAccessAdmin(): bool
    {
        return $this === self::Admin;
    }

    /** 稽核角色唯讀：任何頁面都不該出現寫入按鈕，送出寫入請求也會被 Policy 拒絕。 */
    public function isReadOnly(): bool
    {
        return $this === self::Auditor;
    }

    /** 可進入審核佇列的角色。 */
    public function canReview(): bool
    {
        return in_array($this, [self::DeptHead, self::Reviewer, self::Manager], true);
    }

    /** 可填報的角色（自己部門的排放源）。 */
    public function canEnterData(): bool
    {
        return in_array($this, [self::Contributor, self::DeptHead], true);
    }

    /** 登入後的首頁：階段 0 只有管理員有後台，其餘角色先到共用首頁。 */
    public function homeRoute(): string
    {
        return $this === self::Admin ? 'admin.users.index' : 'home';
    }
}
