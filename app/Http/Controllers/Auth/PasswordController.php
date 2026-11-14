<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\StrongPassword;
use App\Services\Audit\AuditWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            // 換掉記住我權杖，舊 cookie 立即失效
            'remember_token' => Str::random(60),
        ])->save();

        // 其他裝置的登入立即失效（含資料庫 session 的銷毀）
        Auth::logoutOtherDevices($validated['password']);

        $request->session()->regenerate();

        $this->audit->record('password.changed', $user, null, null, $user);

        return redirect()->route('home')->with('status', '密碼已更新，其他裝置需要重新登入。');
    }
}
