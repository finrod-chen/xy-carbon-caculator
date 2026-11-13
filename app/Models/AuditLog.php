<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * 稽核紀錄（WP0.3）：只增不改，以 prev_hash / hash 串成雜湊鏈。
 * 資料庫層另有 trigger 擋 UPDATE / DELETE，這裡是應用層第二道防線。
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = [
        'occurred_at' => 'datetime',
        'before' => 'array',
        'after' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('稽核紀錄不可修改');
        });

        static::deleting(function () {
            throw new RuntimeException('稽核紀錄不可刪除');
        });
    }
}
