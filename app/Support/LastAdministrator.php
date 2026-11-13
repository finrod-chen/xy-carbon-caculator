<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * 系統必須至少保留一位啟用的管理員。
 * 最後一位管理員不可把自己降級或停用，否則沒人能再進後台。
 */
final class LastAdministrator
{
    public static function activeAdminCount(): int
    {
        return User::query()
            ->where('role', Role::Admin->value)
            ->where('is_active', true)
            ->count();
    }

    /**
     * 變更或停用前呼叫；會拋出驗證錯誤由表單顯示。
     *
     * @throws ValidationException
     */
    public static function guard(User $target): void
    {
        if ($target->role !== Role::Admin || ! $target->is_active) {
            return;
        }

        if (self::activeAdminCount() <= 1) {
            throw ValidationException::withMessages([
                'role' => '系統必須至少保留一位啟用的管理員，請先指派另一位管理員再變更。',
            ]);
        }
    }
}
