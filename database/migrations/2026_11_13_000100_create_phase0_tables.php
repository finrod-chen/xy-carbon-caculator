<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 階段 0 資料表（TECH_DESIGN 第 12 節）。
 * 骨架已建立 users / sessions / cache / jobs / failed_jobs，這裡只補上系統需要的欄位與其餘表。
 * 所有表都能在 SQLite 與 MySQL 8 執行；列舉以 enum() 產生 CHECK 約束。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('tax_id', 20)->nullable();
            $table->enum('boundary_approach', ['operational_control', 'financial_control', 'equity_share'])
                ->default('operational_control');
            $table->string('country', 2)->default('TW');
            $table->unsignedTinyInteger('fiscal_year_start_month')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('floor_area_m2', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // users 由骨架建立，這裡補上角色與帳號狀態欄位（WP0.4 / WP0.5）
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['contributor', 'dept_head', 'reviewer', 'manager', 'esg', 'admin', 'auditor'])
                ->default('contributor');
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('locked_until')->nullable();
        });

        Schema::create('references', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // R-01 …
            $table->string('title');
            $table->string('publisher');
            $table->string('version_label')->nullable();
            $table->date('published_on')->nullable();
            $table->text('url')->nullable();
            $table->text('purpose');
            $table->enum('verification_status', ['original', 'secondary', 'pending'])->default('pending');
            $table->date('verified_on')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('review_due_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('verification_status');
        });

        Schema::create('page_references', function (Blueprint $table) {
            $table->id();
            $table->string('page_slug');
            $table->foreignId('reference_id')->constrained('references')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['page_slug', 'reference_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('group', 50)->default('general');
            $table->text('value')->nullable();
            $table->string('value_type', 20)->default('string');
            $table->string('description')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 稽核紀錄：只增不改，以 prev_hash / hash 串成雜湊鏈（WP0.3）
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('occurred_at');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 50); // created / updated / deleted / login / logout / export …
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->longText('before')->nullable();
            $table->longText('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('prev_hash', 64)->nullable();
            $table->string('hash', 64);
            $table->timestamp('created_at')->nullable();
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['actor_id', 'occurred_at']);
            $table->index('event');
        });

        $this->createAppendOnlyTriggers();
    }

    public function down(): void
    {
        $this->dropAppendOnlyTriggers();

        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('page_references');
        Schema::dropIfExists('references');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn(['role', 'is_active', 'must_change_password', 'last_login_at', 'locked_until']);
        });

        Schema::dropIfExists('departments');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('entities');
    }

    /** 資料庫層第二道防線：稽核紀錄不可更新或刪除。 */
    private function createAppendOnlyTriggers(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("CREATE TRIGGER audit_logs_no_update BEFORE UPDATE ON audit_logs BEGIN SELECT RAISE(ABORT, 'audit_logs is append-only'); END");
            DB::statement("CREATE TRIGGER audit_logs_no_delete BEFORE DELETE ON audit_logs BEGIN SELECT RAISE(ABORT, 'audit_logs is append-only'); END");
        } elseif (DB::getDriverName() === 'mysql') {
            DB::unprepared("CREATE TRIGGER audit_logs_no_update BEFORE UPDATE ON audit_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs is append-only'");
            DB::unprepared("CREATE TRIGGER audit_logs_no_delete BEFORE DELETE ON audit_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs is append-only'");
        }
    }

    private function dropAppendOnlyTriggers(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS audit_logs_no_update');
        DB::statement('DROP TRIGGER IF EXISTS audit_logs_no_delete');
    }
};
