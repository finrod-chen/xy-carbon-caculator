<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** 修改密碼；臨時密碼必須先改才能使用系統（PHASE0 WP0.4）。 */
class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => [
                'required', 'string', 'confirmed',
                Password::min(12)->letters()->numbers(),
                'different:current_password',
            ],
        ]);

        $request->user()->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
            'locked_until' => null,
        ])->save();

        $request->session()->regenerate();

        return redirect()->route('home')->with('status', '密碼已更新。');
    }
}
