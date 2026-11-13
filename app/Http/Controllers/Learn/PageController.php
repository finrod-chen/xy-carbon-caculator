<?php

namespace App\Http\Controllers\Learn;

use App\Http\Controllers\Controller;
use App\Models\Reference;
use App\Support\Content\Page;
use App\Support\Content\PageRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** 說明頁與參考資料頁（PHASE0 WP0.7、WP0.8 的唯讀部分）。 */
class PageController extends Controller
{
    public function index(PageRepository $pages): View
    {
        return view('learn.index', [
            'pages' => $pages->all(),
        ]);
    }

    public function show(Request $request, PageRepository $pages, string $slug): View
    {
        $page = $pages->find($slug);

        abort_if($page === null, 404);

        $all = $pages->all();
        $slugs = array_keys($all);
        $position = array_search($slug, $slugs, true);

        return view('learn.show', [
            'page' => $page,
            'pages' => $all,
            'previous' => $position > 0 ? $all[$slugs[$position - 1]] : null,
            'next' => $position !== false && isset($slugs[$position + 1]) ? $all[$slugs[$position + 1]] : null,
            'references' => $this->referencesFor($page),
            'hasUnverified' => $this->referencesFor($page)->contains(fn (Reference $r) => $r->needsRecheck()),
        ]);
    }

    public function references(): View
    {
        return view('learn.references', [
            'references' => Reference::query()->orderBy('code')->get(),
        ]);
    }

    /** @return \Illuminate\Support\Collection<int, Reference> */
    private function referencesFor(Page $page): \Illuminate\Support\Collection
    {
        if ($page->referenceCodes === []) {
            return collect();
        }

        return Reference::query()
            ->whereIn('code', $page->referenceCodes)
            ->orderBy('code')
            ->get();
    }
}
