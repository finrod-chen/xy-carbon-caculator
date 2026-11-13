<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditWriter;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * 自建帳號登入（PHASE0 WP0.4）。
 *
 * 節流分兩個鍵：帳號一組（換 IP 也繞不過）與來源 IP 一組（擋單一來源暴力猜測）。
 * 超過門檻會把 users.locked_until 寫進去，管理員可從後台重設解鎖。
 * 帳號不存在時仍跑一次同成本 bcrypt，避免用回應時間判斷帳號是否存在。
 */
class LoginController extends Controller
{
    private const MAX_ATTEMPTS_PER_EMAIL = 5;

    private const MAX_ATTEMPTS_PER_IP = 20;

    private const DECAY_SECONDS = 900;

    public function __construct(private readonly AuditWriter $audit) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = (string) $credentials['email'];
        $emailKey = $this->emailKey($email);
        $ipKey = $this->ipKey($request);

        if ($this->tooManyAttempts($emailKey, $ipKey)) {
            return $this->failed($request, $this->throttleMessage($emailKey, $ipKey));
        }

        $user = User::query()->where('email', $email)->first();

        $passwordMatches = Hash::check($credentials['password'], $user?->password ?? $this->dummyHash());

        if ($user === null || ! $passwordMatches || ! $user->is_active || $this->isLocked($user)) {
            $this->registerFailure($request, $user, $email);

            return $this->failed($request, '帳號或密碼不正確。');
        }

        RateLimiter::clear($emailKey);
        Auth::login($user);                 // 不提供「記住我」：登入狀態受閒置上限約束
        $request->session()->regenerate();
        $request->session()->put('last_activity_at', now()->toIso8601String());

        $user->forceFill(['last_login_at' => now(), 'locked_until' => null])->save();

        $this->audit->record('login.succeeded', $user, null, ['email' => $user->email], $user);

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            $this->audit->record('logout', $user, null, null, $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function failed(Request $request, string $message): RedirectResponse
    {
        return back()->withInput($request->only('email'))->withErrors(['email' => $message]);
    }

    private function tooManyAttempts(string $emailKey, string $ipKey): bool
    {
        return RateLimiter::tooManyAttempts($emailKey, self::MAX_ATTEMPTS_PER_EMAIL)
            || RateLimiter::tooManyAttempts($ipKey, self::MAX_ATTEMPTS_PER_IP);
    }

    private function throttleMessage(string $emailKey, string $ipKey): string
    {
        $seconds = max(RateLimiter::availableIn($emailKey), RateLimiter::availableIn($ipKey));
        $minutes = max(1, (int) ceil($seconds / 60));

        return "登入失敗次數過多，請於 {$minutes} 分鐘後再試。";
    }

    private function registerFailure(Request $request, ?User $user, string $email): void
    {
        $emailKey = $this->emailKey($email);

        RateLimiter::hit($emailKey, self::DECAY_SECONDS);
        RateLimiter::hit($this->ipKey($request), self::DECAY_SECONDS);

        if ($user !== null && RateLimiter::attempts($emailKey) >= self::MAX_ATTEMPTS_PER_EMAIL) {
            $user->forceFill([
                'locked_until' => now()->addMinutes((int) config('carbon.auth.lockout_minutes', 15)),
            ])->save();
        }

        $this->audit->record('login.failed', $user, null, ['email' => $email], null);
    }

    private function isLocked(User $user): bool
    {
        if ($user->locked_until === null) {
            return false;
        }

        return CarbonImmutable::parse($user->locked_until)->isFuture();
    }

    private function emailKey(string $email): string
    {
        return 'login:email:'.sha1(mb_strtolower($email));
    }

    private function ipKey(Request $request): string
    {
        return 'login:ip:'.($request->ip() ?? 'unknown');
    }

    private function dummyHash(): string
    {
        return Cache::rememberForever('auth.dummy_password_hash', fn (): string => Hash::make(Str::random(40)));
    }
}
