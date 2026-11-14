<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

/**
 * 使用者管理的授權（TECH_DESIGN 第 4 節）。
 * 管理員可讀寫；稽核唯讀角色可看清單，但不得建立或修改。
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [Role::Admin, Role::Auditor], true);
    }

    public function view(User $user, User $target): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->role === Role::Admin;
    }

    public function update(User $user, User $target): bool
    {
        return $user->role === Role::Admin;
    }
}
