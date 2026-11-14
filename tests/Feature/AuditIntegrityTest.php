<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Services\Audit\AuditWriter;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/** WP0.3：稽核紀錄只增不改，應用層與資料庫層都要擋；改過要抓得到。 */
class AuditIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => '稽核完整性',
            'email' => uniqid('audit').'@example.com',
            'password' => 'password123456',
            'role' => Role::Contributor,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_model_blocks_update_and_delete(): void
    {
        $log = app(AuditWriter::class)->record('test.created');

        try {
            $log->forceFill(['event' => 'changed'])->save();
            $this->fail('應用層應該擋下更新');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('不可修改', $e->getMessage());
        }

        $this->expectException(RuntimeException::class);
        $log->delete();
    }

    public function test_database_trigger_blocks_update_and_delete(): void
    {
        $log = app(AuditWriter::class)->record('test.created');

        try {
            DB::table('audit_logs')->where('id', $log->id)->update(['event' => 'changed']);
            $this->fail('trigger 應該擋下更新');
        } catch (QueryException $e) {
            $this->assertNotSame('', $e->getMessage());
        }

        $this->expectException(QueryException::class);
        DB::table('audit_logs')->where('id', $log->id)->delete();
    }

    public function test_verify_reports_the_tampered_row_id(): void
    {
        $writer = app(AuditWriter::class);
        $writer->record('test.first');

        $forgedId = DB::table('audit_logs')->insertGetId([
            'occurred_at' => now(),
            'event' => 'test.forged',
            'hash' => str_repeat('0', 64),
            'prev_hash' => null,
            'created_at' => now(),
        ]);

        $this->assertSame((int) $forgedId, $writer->verifyChain());
        $this->artisan('audit:verify')->assertFailed();
    }

    public function test_password_material_never_reaches_audit_logs(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password123456',
            'password' => 'Vz7#tqwmKpr9LduX',
            'password_confirmation' => 'Vz7#tqwmKpr9LduX',
        ])->assertRedirect(route('home'));

        $dump = (string) json_encode(DB::table('audit_logs')->get());

        $this->assertStringNotContainsString('Vz7#tqwmKpr9LduX', $dump);
        $this->assertStringNotContainsString('$2y$', $dump);
        $this->assertDatabaseHas('audit_logs', ['event' => 'password.changed']);
    }
}
