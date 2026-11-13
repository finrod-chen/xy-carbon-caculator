<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

/**
 * 一次性顯示臨時密碼。
 *
 * 不再放進 flash：flash 會寫進 sessions 資料表，未開啟加密時是明文。
 * 改成快取一筆 Crypt 加密後的內容，用一次性 token 取回，顯示一次就刪除。
 */
class TemporaryPasswordController extends Controller
{
    public function show(Request $request, string $token): View|RedirectResponse
    {
        abort_unless($request->user()?->role === Role::Admin, 403);

        $payload = Cache::pull('temp-password:'.$token);

        if (! is_string($payload)) {
            return redirect()->route('admin.users.index')
                ->with('status', '臨時密碼已顯示過或已逾時，請重新產生一次。');
        }

        $data = json_decode(Crypt::decryptString($payload), true) ?: [];

        return view('admin.users.temp-password', [
            'name' => (string) ($data['user'] ?? ''),
            'email' => (string) ($data['email'] ?? ''),
            'password' => (string) ($data['password'] ?? ''),
        ]);
    }
}
