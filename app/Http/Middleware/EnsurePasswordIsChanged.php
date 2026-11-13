<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** 首次登入（或以臨時密碼登入）時，強制先改密碼才能使用系統（PHASE0 WP0.4）。 */
class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->must_change_password
            && ! $request->routeIs('password.*')
            && ! $request->routeIs('logout')) {
            return redirect()->route('password.edit')
                ->with('status', '請先修改臨時密碼。');
        }

        return $next($request);
    }
}
