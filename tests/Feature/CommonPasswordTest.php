<?php

namespace Tests\Feature;

use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CommonPasswordTest extends TestCase
{
    private function fails(string $password, ?User $user = null): bool
    {
        return Validator::make(
            ['password' => $password],
            ['password' => [new StrongPassword($user)]],
        )->fails();
    }

    public function test_blacklist_contains_at_least_one_thousand_entries(): void
    {
        $lines = file(resource_path('data/common-passwords.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $entries = array_filter($lines, fn (string $line) => $line !== '' && ! str_starts_with(trim($line), '#'));

        $this->assertGreaterThanOrEqual(1000, count($entries));
    }

    public function test_blacklist_match_is_case_insensitive(): void
    {
        foreach (['letmein', 'LETMEIN', 'moNKey1', 'changeme', 'PassWord1234'] as $weak) {
            $this->assertTrue($this->fails($weak), $weak.' 應該被擋下');
        }
    }

    public function test_strong_password_passes(): void
    {
        $this->assertFalse($this->fails('Vz7#tqwmKpr9LduX'));
    }

    public function test_sequence_and_repetition_are_rejected(): void
    {
        $this->assertTrue($this->fails('Xq7-abcd-Zt9w'));
        $this->assertTrue($this->fails('Xq7-aaaa-Zt9w'));
    }

    public function test_password_cannot_contain_the_users_identity(): void
    {
        $user = new User(['name' => 'Wendy', 'email' => 'wendy@example.com']);

        $this->assertTrue($this->fails('Xq7-wendy-Zt9', $user));
        $this->assertFalse($this->fails('Xq7-tqwm-Zt9w', $user));
    }
}
