<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** 報告個體（TECH_DESIGN 5.1）。目前一筆，為 IPO 後新增子公司或據點預留。 */
class Entity extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'boundary_approach' => 'string',
        'fiscal_year_start_month' => 'integer',
        'is_active' => 'boolean',
    ];

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }
}
