<?php

namespace App\Console\Commands;

use App\Support\Content\PageRepository;
use Illuminate\Console\Command;

/** 檢查說明頁引用的 R-xx 是否都存在（CI 會跑）。 */
class ContentCheckCommand extends Command
{
    protected $signature = 'content:check';

    protected $description = '檢查說明頁引用的參考資料編號是否都已建檔';

    public function handle(PageRepository $pages): int
    {
        $missing = $pages->missingReferences();

        if ($missing === []) {
            $this->info('說明頁引用檢查通過，共 '.count($pages->all()).' 頁。');

            return self::SUCCESS;
        }

        foreach ($missing as $slug => $codes) {
            $this->error($slug.' 缺少參考資料：'.implode('、', $codes));
        }

        return self::FAILURE;
    }
}
