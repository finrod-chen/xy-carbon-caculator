<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** 部門（PLAN 第 5 節）。被使用者參照者不可刪除，只能停用。 */
class Department extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function canBeDeleted(): bool
    {
        return $this->users()->doesntExist();
    }
}
