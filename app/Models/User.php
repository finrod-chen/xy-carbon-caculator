<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * 使用者（PHASE0 WP0.4／0.5）。
 *
 * 這裡明列欄位型別，讓 Larastan 與 IDE 看得懂 enum 與日期轉型（Eloquent 的 casts() 不會被靜態分析解析）。
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property Role $role
 * @property int|null $department_id
 * @property bool $is_active
 * @property bool $must_change_password
 * @property Carbon|null $last_login_at
 * @property Carbon|null $locked_until
 * @property Carbon|null $email_verified_at
 * @property string|null $remember_token
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department_id',
        'is_active',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** 登入節流：連續錯誤 5 次鎖定 15 分鐘（WP0.4）。 */
    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }
}
