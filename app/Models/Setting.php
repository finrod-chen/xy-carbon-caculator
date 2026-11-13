<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** 系統設定（填報時程、異常門檻、提醒天數、捨入規則）。 */
class Setting extends Model
{
    protected $guarded = ['id'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();

        if ($row === null) {
            return $default;
        }

        return match ($row->value_type) {
            'integer' => (int) $row->value,
            'boolean' => (bool) $row->value,
            'json' => json_decode((string) $row->value, true),
            default => $row->value,
        };
    }

    public static function put(string $key, mixed $value, string $type = 'string', string $group = 'general', ?string $description = null): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value,
                'value_type' => $type,
                'group' => $group,
                'description' => $description,
            ]
        );
    }
}
