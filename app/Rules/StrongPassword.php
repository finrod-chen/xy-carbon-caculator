<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 密碼政策（WP0.4）。
 *
 * 1. 不可落在本機黑名單（resources/data/common-passwords.txt，比對不分大小寫）
 * 2. 不可有四個以上重複字元，也不可有四個以上連續字母或數字
 * 3. 不可包含自己的姓名或信箱
 *
 * 長度與字元種類交給 Password::min(...) 檢查。
 */
class StrongPassword implements ValidationRule
{
    /** @var array<int, string>|null */
    private static ?array $blacklist = null;

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

        if (in_array($lower, self::blacklist(), true)) {
            $fail('這個密碼在常見弱密碼清單裡，請換一組。');

            return;
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

    /**
     * 黑名單只讀一次檔，之後留在靜態屬性裡。
     *
     * @return array<int, string>
     */
    private static function blacklist(): array
    {
        if (self::$blacklist !== null) {
            return self::$blacklist;
        }

        $path = resource_path('data/common-passwords.txt');

        if (! is_file($path)) {
            return self::$blacklist = [];
        }

        $list = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $list[] = mb_strtolower($line);
        }

        return self::$blacklist = $list;
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
