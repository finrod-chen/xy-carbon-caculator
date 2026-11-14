<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/** 元件展示頁（PHASE0 WP0.6）；只在 local 與 testing 環境開放。 */
class ShowcaseController extends Controller
{
    public function __invoke(): View
    {
        abort_unless(app()->environment('local', 'testing'), 404);

        return view('showcase');
    }
}
