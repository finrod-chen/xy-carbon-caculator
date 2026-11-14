<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** 規格：部門被使用者參照時不可刪除。 */
class DepartmentDeleteRuleTest extends TestCase
{
    use RefreshDatabase;

    private function department(string $code): Department
    {
        $department = new Department;
        $department->forceFill(['name' => '測試部門 '.$code, 'code' => $code])->save();

        return $department;
    }

    public function test_department_with_users_cannot_be_deleted(): void
    {
        $department = $this->department('TST');

        User::create([
            'name' => '部門成員',
            'email' => 'member@example.com',
            'password' => 'password123456',
            'role' => Role::Contributor,
            'department_id' => $department->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $this->expectException(QueryException::class);

        DB::table('departments')->where('id', $department->id)->delete();
    }

    public function test_department_without_users_can_be_deleted(): void
    {
        $department = $this->department('EMP');

        DB::table('departments')->where('id', $department->id)->delete();

        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }
}
