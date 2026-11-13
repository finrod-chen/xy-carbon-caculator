<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** 使用者管理（PHASE0 WP0.9 的使用者部分）：建立、修改、停用、重設臨時密碼。 */
class UserController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('admin.users.index', [
            'users' => User::query()->with('department')->orderBy('name')->paginate(20),
            'canManage' => Gate::allows('create', User::class),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.form', [
            'user' => new User(['is_active' => true]),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'roles' => Role::cases(),
            'action' => route('admin.users.store'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $validated = $this->validateUser($request);
        $temporaryPassword = Str::password(16);

        User::create([
            ...$validated,
            'password' => $temporaryPassword,
            'is_active' => true,
            'must_change_password' => true,
        ]);

        return redirect()->route('admin.users.index')->with('status', '使用者已建立。臨時密碼：'.$temporaryPassword);
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.form', [
            'user' => $user,
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'roles' => Role::cases(),
            'action' => route('admin.users.update', $user),
            'method' => 'PUT',
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $user->update($this->validateUser($request, $user));

        return redirect()->route('admin.users.index')->with('status', '使用者資料已更新。');
    }

    public function deactivate(User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        abort_if($user->is(auth()->user()), 403, '不可停用自己。');

        $user->update(['is_active' => false]);

        return back()->with('status', '使用者已停用，既有登入立即失效。');
    }

    public function reactivate(User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $user->update(['is_active' => true]);

        return back()->with('status', '使用者已重新啟用。');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $temporaryPassword = Str::password(16);

        $user->forceFill([
            'password' => $temporaryPassword,
            'must_change_password' => true,
            'locked_until' => null,
        ])->save();

        return back()->with('status', '已重設臨時密碼：'.$temporaryPassword);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUser(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'role' => ['required', Rule::enum(Role::class)],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);
    }
}
