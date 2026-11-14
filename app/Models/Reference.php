<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** 參考資料（PLAN 第 11 節的 R-01…R-14）。係數、GWP、SBTi 參數都以此表為出處。 */
class Reference extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'verification_status' => VerificationStatus::class,
        'published_on' => 'date',
        'verified_on' => 'date',
        'review_due_on' => 'date',
    ];

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function pageReferences(): HasMany
    {
        return $this->hasMany(PageReference::class);
    }

    /** 核對日超過 12 個月即需複核（WP0.8 驗收：13 個月前要顯示「需複核」）。 */
    public function needsRecheck(): bool
    {
        if ($this->verification_status !== VerificationStatus::Original) {
            return true;
        }

        return $this->verified_on === null || $this->verified_on->lt(now()->subYear());
    }
}
