<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 場址。目前一筆；樓地板面積之後作為強度指標分母（PLAN 4.4）。 */
class Site extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'floor_area_m2' => 'decimal:2',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }
}
