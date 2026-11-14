<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 閒置超時（WP0.4）：超過 carbon.auth.session_timeout_minutes 就登出。
 * 系統不提供「記住我」，登入狀態最長就是這個上限。
 * 時間以 Unix 秒數存放，避免時區與 Carbon 版本的差異。
 */
class EnforceIdleTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        $minutes = (int) config('carbon.auth.session_timeout_minutes', 120);
        $lastSeen = $request->session()->get('last_activity_at');

        if (is_numeric($lastSeen) && (time() - (int) $lastSeen) >= $minutes * 60) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => "閒置超過 {$minutes} 分鐘，請重新登入。",
            ]);
        }

        $request->session()->put('last_activity_at', time());

        return $next($request);
    }
}
