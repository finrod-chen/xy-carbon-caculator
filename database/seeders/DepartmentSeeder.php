<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

/** 部門種子（PLAN 第 5 節）；可重複執行。 */
class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['code' => 'ADM', 'name' => '行政總務'],
            ['code' => 'PROC', 'name' => '採購財務'],
            ['code' => 'RD', 'name' => '研發實驗室'],
            ['code' => 'HR', 'name' => '人資'],
            ['code' => 'MGMT', 'name' => '管理'],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(['code' => $department['code']], $department);
        }
    }
}
