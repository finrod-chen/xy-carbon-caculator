<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Services\Audit\AuditWriter;
use Illuminate\Console\Command;

/** 驗證稽核雜湊鏈（WP0.3）；鏈斷掉時以非 0 離開碼回報，可直接放進維運排程。 */
class AuditVerifyCommand extends Command
{
    protected $signature = 'audit:verify';

    protected $description = '驗證稽核紀錄的雜湊鏈是否完整';

    public function handle(AuditWriter $writer): int
    {
        $brokenAt = $writer->verifyChain();

        if ($brokenAt !== null) {
            $this->error($brokenAt > 0
                ? "稽核雜湊鏈在第 {$brokenAt} 筆斷裂。"
                : '稽核鏈尾與 audit_chain 不一致。');

            return self::FAILURE;
        }

        $this->info('稽核雜湊鏈完整，共 '.AuditLog::query()->count().' 筆。');

        return self::SUCCESS;
    }
}
