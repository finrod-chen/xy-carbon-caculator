<?php

namespace Tests\Feature;

use App\Enums\VerificationStatus;
use App\Models\Reference;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 重跑 seed 不可以蓋掉人工維護的資料。 */
class SeederIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_seeder_keeps_manual_verification(): void
    {
        $this->seed(ReferenceSeeder::class);

        Reference::query()->where('code', 'R-02')->update([
            'verification_status' => VerificationStatus::Original,
            'verified_on' => '2026-11-10',
        ]);

        $this->seed(ReferenceSeeder::class);

        $reference = Reference::query()->where('code', 'R-02')->firstOrFail();

        $this->assertSame(VerificationStatus::Original, $reference->verification_status);
        $this->assertSame('2026-11-10', $reference->verified_on?->toDateString());
    }

    public function test_database_seeder_keeps_the_existing_admin_password(): void
    {
        config([
            'carbon.admin.email' => 'boss@example.com',
            'carbon.admin.name' => '老闆',
            'carbon.admin.initial_password' => 'InitPass-9z8x',
        ]);

        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'boss@example.com')->firstOrFail();
        $original = $admin->password;

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($original, $admin->fresh()->password);
    }

    public function test_database_seeder_skips_when_password_is_missing(): void
    {
        config([
            'carbon.admin.email' => 'nopassword@example.com',
            'carbon.admin.initial_password' => null,
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseMissing('users', ['email' => 'nopassword@example.com']);
    }
}
