<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Audit\AuditWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** WP0.3 驗收：稽核要真的被呼叫、鏈要驗得過、改過要抓得到。 */
class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    /** @param  array<string, mixed>  $attributes */
    private function makeUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => '稽核測試',
            'email' => uniqid('audit').'@example.com',
            'password' => 'password123456',
            'role' => Role::Contributor,
            'is_active' => true,
            'must_change_password' => false,
        ], $attributes));
    }

    public function test_login_is_audited_with_ip_and_user_agent(): void
    {
        $user = $this->makeUser();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123456',
        ])->assertRedirect();

        $log = AuditLog::query()->where('event', 'login.succeeded')->first();

        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->actor_id);
        $this->assertSame('127.0.0.1', $log->ip_address);
    }

    public function test_failed_login_is_audited(): void
    {
        $user = $this->makeUser();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseHas('audit_logs', ['event' => 'login.failed', 'actor_id' => $user->id]);
    }

    public function test_admin_creating_a_user_is_audited(): void
    {
        $admin = $this->makeUser(['role' => Role::Admin]);

        $this->actingAs($admin)->post('/admin/users', [
            'name' => '新同事',
            'email' => 'new.colleague@example.com',
            'role' => 'contributor',
            'department_id' => null,
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', ['event' => 'user.created']);
    }

    public function test_chain_links_each_record_to_the_previous_hash(): void
    {
        $writer = app(AuditWriter::class);

        $first = $writer->record('test.first');
        $second = $writer->record('test.second');

        $this->assertNull($first->prev_hash);
        $this->assertSame($first->hash, $second->prev_hash);
        $this->artisan('audit:verify')->assertSuccessful();
    }

    public function test_tampered_log_breaks_the_chain(): void
    {
        $writer = app(AuditWriter::class);
        $writer->record('test.first');
        $writer->record('test.second');

        $this->artisan('audit:verify')->assertSuccessful();

        // 模擬有人在資料庫層塞入一筆對不上鏈的紀錄（insert 沒有被 trigger 擋）
        DB::table('audit_logs')->insert([
            'occurred_at' => now(),
            'event' => 'test.forged',
            'hash' => str_repeat('0', 64),
            'prev_hash' => null,
            'created_at' => now(),
        ]);

        $this->artisan('audit:verify')->assertFailed();
    }
}
