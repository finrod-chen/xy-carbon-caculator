<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            ReferenceSeeder::class,
        ]);

        $this->createInitialAdmin();
    }

    /**
     * 初始管理員：信箱與密碼由安裝時以環境變數提供，不寫死在程式或文件中（PHASE0 前置條件）。
     */
    private function createInitialAdmin(): void
    {
        $email = env('ADMIN_EMAIL');

        if (! $email) {
            $this->command?->warn('未設定 ADMIN_EMAIL，略過建立初始管理員。');

            return;
        }

        $password = env('ADMIN_INITIAL_PASSWORD');

        if (! $password) {
            $password = Str::password(16);
            $this->command?->warn("初始管理員臨時密碼：{$password}（首次登入須修改）");
        }

        User::updateOrCreate(['email' => $email], [
            'name' => env('ADMIN_NAME', '系統管理員'),
            'password' => Hash::make($password),
            'role' => Role::Admin,
            'is_active' => true,
            'must_change_password' => true,
        ]);
    }
}
