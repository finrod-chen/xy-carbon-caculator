<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/** 首頁：階段 0 先依角色顯示可進入的功能，其餘模組待後續階段接上。 */
class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('home', [
            'user' => $request->user(),
        ]);
    }
}
