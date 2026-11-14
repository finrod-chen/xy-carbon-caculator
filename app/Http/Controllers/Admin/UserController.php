<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Services\Audit\AuditWriter;
use App\Support\LastAdministrator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * 使用者管理（WP0.9 使用者部分）：建立、修改、停用、重設臨時密碼。
 * 所有寫入都留稽核紀錄；臨時密碼走一次性頁面，不經過 flash／session。
 */
class UserController extends Controller
{
    public function __construct(private readonly AuditWriter $audit) {}

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
        $password = Str::password(16);

        $user = User::create([
            ...$validated,
            'password' => $password,
            'is_active' => true,
            'must_change_password' => true,
        ]);

        $this->audit->record('user.created', $user, null, $this->snapshot($user), $request->user());

        return $this->temporaryPasswordRedirect($user, $password);
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

        $validated = $this->validateUser($request, $user);
        $before = $this->snapshot($user);

        // 降級前先確認不會把最後一位管理員降掉
        DB::transaction(function () use ($user, $validated): void {
            if (($validated['role'] ?? null) !== Role::Admin->value) {
                LastAdministrator::guard($user);
            }

            $user->update($validated);
        });

        $this->audit->record('user.updated', $user, $before, $this->snapshot($user->refresh()), $request->user());

        return redirect()->route('admin.users.index')->with('status', '使用者資料已更新。');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        abort_if($user->is($request->user()), 403, '不可停用自己。');

        DB::transaction(function () use ($user): void {
            LastAdministrator::guard($user);
            $user->update(['is_active' => false]);
        });

        $this->audit->record('user.deactivated', $user, ['is_active' => true], ['is_active' => false], $request->user());

        return back()->with('status', '使用者已停用，既有登入立即失效。');
    }

    public function reactivate(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $user->update(['is_active' => true]);

        $this->audit->record('user.reactivated', $user, ['is_active' => false], ['is_active' => true], $request->user());

        return back()->with('status', '使用者已重新啟用。');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        // 對自己重設會把目前的工作階段踢掉，而且看不到臨時密碼；改走「修改密碼」流程
        abort_if($user->is($request->user()), 403, '請使用「修改密碼」頁面變更自己的密碼。');

        $password = Str::password(16);

        $user->forceFill([
            'password' => $password,
            'must_change_password' => true,
            'locked_until' => null,
            'remember_token' => Str::random(60),
        ])->save();

        // 重設後把該使用者所有裝置的 session 清掉
        $this->forgetSessions($user, $request->session()->getId());

        $this->audit->record('user.password_reset', $user, null, ['email' => $user->email], $request->user());

        return $this->temporaryPasswordRedirect($user, $password);
    }

    /** 臨時密碼只顯示一次：加密後放快取，用一次性 token 取回。 */
    private function temporaryPasswordRedirect(User $user, string $password): RedirectResponse
    {
        $token = Str::random(48);

        Cache::put(
            'temp-password:'.$token,
            Crypt::encryptString((string) json_encode([
                'user' => $user->name,
                'email' => $user->email,
                'password' => $password,
            ], JSON_UNESCAPED_UNICODE)),
            now()->addMinutes(10),
        );

        return redirect()->route('admin.users.temp-password', ['token' => $token]);
    }

    private function forgetSessions(User $user, ?string $keepSessionId = null): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $user->getAuthIdentifier())
            ->when($keepSessionId !== null, fn ($query) => $query->where('id', '<>', $keepSessionId))
            ->delete();
    }

    /** @return array<string, mixed> */
    private function snapshot(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'department_id' => $user->department_id,
            'is_active' => (bool) $user->is_active,
        ];
    }

    /** @return array<string, mixed> */
    private function validateUser(Request $request, ?User $user = null): array
    {
        // 電子郵件一律小寫，避免 SQLite 的大小寫差異造成重複帳號
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

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
