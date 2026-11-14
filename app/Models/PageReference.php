<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 說明頁引用了哪些出處（[R-xx] 標註的來源）。 */
class PageReference extends Model
{
    protected $guarded = ['id'];

    public function reference(): BelongsTo
    {
        return $this->belongsTo(Reference::class);
    }
}
