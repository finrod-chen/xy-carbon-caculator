<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 密碼政策（WP0.4）：擋掉「看起來合格、其實很好猜」的密碼。
 * 長度與字元種類由 Password::min(...) 檢查，這裡補常見字串、重複字元與鍵盤序列。
 */
class StrongPassword implements ValidationRule
{
    /** @var array<int, string> */
    private const COMMON = [
        'password', 'passw0rd', 'qwerty', 'abc123', '123456', 'letmein',
        'welcome', 'iloveyou', 'admin123', 'changeme', 'taiwan', 'carbon',
    ];

    /** @var array<int, string> */
    private const SEQUENCES = [
        '0123456789',
        'abcdefghijklmnopqrstuvwxyz',
        'qwertyuiop',
        'asdfghjkl',
        'zxcvbnm',
    ];

    public function __construct(private readonly ?User $user = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $lower = mb_strtolower($value);

        foreach (self::COMMON as $weak) {
            if (str_contains($lower, $weak)) {
                $fail('密碼不可包含常見的弱密碼字串（例如 Password1234）。');

                return;
            }
        }

        if (preg_match('/(.)\1{3,}/u', $value) === 1) {
            $fail('密碼不可有四個以上重複的字元。');

            return;
        }

        if ($this->hasSequentialRun($lower)) {
            $fail('密碼不可包含四個以上的連續字母或數字（例如 1234、abcd）。');

            return;
        }

        if ($this->user !== null && $this->containsIdentity($lower)) {
            $fail('密碼不可包含自己的姓名或電子郵件。');
        }
    }

    private function hasSequentialRun(string $lower): bool
    {
        foreach (self::SEQUENCES as $sequence) {
            $reversed = strrev($sequence);
            $length = strlen($sequence);

            for ($offset = 0; $offset <= $length - 4; $offset++) {
                if (str_contains($lower, substr($sequence, $offset, 4))
                    || str_contains($lower, substr($reversed, $offset, 4))) {
                    return true;
                }
            }
        }

        return false;
    }

    private function containsIdentity(string $lower): bool
    {
        $local = mb_strtolower((string) strtok((string) $this->user->email, '@'));
        $name = mb_strtolower((string) $this->user->name);

        foreach ([$local, $name] as $needle) {
            if (mb_strlen($needle) >= 3 && str_contains($lower, $needle)) {
                return true;
            }
        }

        return false;
    }
}
