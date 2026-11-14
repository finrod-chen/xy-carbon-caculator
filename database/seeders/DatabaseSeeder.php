<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
     * 初始管理員：設定放 config/carbon.php，跑過 config:cache 也讀得到。
     * 用 firstOrCreate 語意：重跑 seed 不會把已改過的密碼蓋回臨時密碼。
     * 沒有提供密碼就跳過並提示，不把任何密碼印到日誌（CI 部署日誌會留）。
     */
    private function createInitialAdmin(): void
    {
        $email = config('carbon.admin.email');

        if (! is_string($email) || $email === '') {
            $this->command?->warn('未設定 ADMIN_EMAIL，略過建立初始管理員。');

            return;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->command?->info('初始管理員已存在，保留現有密碼與狀態。');

            return;
        }

        $password = config('carbon.admin.initial_password');

        if (! is_string($password) || $password === '') {
            $this->command?->warn('未設定 ADMIN_INITIAL_PASSWORD，略過建立初始管理員。');

            return;
        }

        User::create([
            'name' => (string) config('carbon.admin.name', '系統管理員'),
            'email' => $email,
            'password' => Hash::make($password),
            'role' => Role::Admin,
            'is_active' => true,
            'must_change_password' => true,
        ]);
    }
}
