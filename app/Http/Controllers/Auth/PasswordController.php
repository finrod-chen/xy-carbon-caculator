<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\StrongPassword;
use App\Services\Audit\AuditWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** 修改密碼；臨時密碼必須先改才能使用系統（PHASE0 WP0.4）。 */
class PasswordController extends Controller
{
    public function __construct(private readonly AuditWriter $audit) {}

    public function edit(): View
    {
        return view('auth.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $minLength = (int) config('carbon.auth.password_min_length', 12);

        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => [
                'required', 'string', 'confirmed',
                Password::min($minLength)->letters()->numbers(),
                new StrongPassword($user),
                'different:current_password',
            ],
        ]);

        $user->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
            'locked_until' => null,
            // 換掉記住我權杖，舊的 cookie 立即失效
            'remember_token' => Str::random(60),
        ])->save();

        $this->logoutOtherDevices($request);

        $request->session()->regenerate();

        $this->audit->record('password.changed', $user, null, null, $user);

        return redirect()->route('home')->with('status', '密碼已更新，其他裝置需要重新登入。');
    }

    /** 讓其他裝置的登入失效：資料庫 session 直接刪除，其他驅動靠密碼雜湊比對。 */
    private function logoutOtherDevices(Request $request): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('id', '!=', $request->session()->getId())
            ->delete();
    }
}
