<?php

namespace App\Support\Content;

use App\Models\Reference;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

/**
 * 說明頁來源：resources/content/**\/*.md（PHASE0 WP0.7）。
 *
 * 執行期不因為缺參考資料就丟例外：未知的 [R-xx] 保留成純文字並記 Log::warning，
 * 真正的把關放在 content:check 與 CI（ContentReferenceIntegrityTest）。
 * 轉換結果依檔案 mtime 快取，內容沒變就不重新解析 Markdown。
 */
final class PageRepository
{
    /** @var array<string, Page>|null */
    private ?array $pages = null;

    /** @return array<string, Page> */
    public function all(): array
    {
        return $this->pages ??= $this->load();
    }

    public function find(string $slug): ?Page
    {
        return $this->all()[$slug] ?? null;
    }

    /**
     * 所有頁面缺少的參考資料（slug => [R-xx, …]），給 content:check 用。
     *
     * @return array<string, array<int, string>>
     */
    public function missingReferences(): array
    {
        $missing = [];

        foreach ($this->all() as $slug => $page) {
            if ($page->missingReferences !== []) {
                $missing[$slug] = $page->missingReferences;
            }
        }

        return $missing;
    }

    /** @return array<string, Page> */
    private function load(): array
    {
        $root = resource_path('content');

        if (! is_dir($root)) {
            return [];
        }

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        $sources = [];

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'md') {
                continue;
            }

            $path = $file->getPathname();
            $relative = str_replace($root.DIRECTORY_SEPARATOR, '', $path);
            $slug = str_replace('.md', '', str_replace(DIRECTORY_SEPARATOR, '/', $relative));

            $sources[$slug] = ['path' => $path, 'mtime' => (int) filemtime($path)];
        }

        $known = $this->knownReferenceCodes($sources);
        $pages = [];

        foreach ($sources as $slug => $source) {
            $key = 'content:'.md5($slug.'|'.$source['mtime'].'|'.implode(',', $known));

            $data = Cache::remember(
                $key,
                now()->addMinutes((int) config('carbon.content.cache_minutes', 1440)),
                fn (): array => $this->build($slug, $source['path'], $known),
            );

            $pages[$slug] = new Page(
                slug: $slug,
                title: $data['title'],
                order: $data['order'],
                owner: $data['owner'],
                updated: $data['updated'],
                referenceCodes: $data['codes'],
                headings: $data['headings'],
                html: $data['html'],
                missingReferences: $data['missing'],
            );
        }

        uasort($pages, fn (Page $a, Page $b) => $a->order <=> $b->order ?: strcmp($a->title, $b->title));

        return $pages;
    }

    /**
     * @param  array<int, string>  $known
     * @return array{title: string, order: int, owner: string|null, updated: string|null, codes: array<int, string>, missing: array<int, string>, headings: array<int, array{id: string, text: string}>, html: string}
     */
    private function build(string $slug, string $path, array $known): array
    {
        [$front, $markdown] = $this->splitFrontMatter((string) file_get_contents($path));

        $codes = $this->referenceCodes($markdown);
        $missing = array_values(array_diff($codes, $known));

        if ($missing !== []) {
            Log::warning('說明頁引用了不存在的參考資料', ['slug' => $slug, 'missing' => $missing]);
        }

        [$html, $headings] = $this->render($this->converter(), $markdown, $known);

        return [
            'title' => (string) ($front['title'] ?? $slug),
            'order' => (int) ($front['order'] ?? 99),
            'owner' => $front['owner'] ?? null,
            'updated' => $front['updated'] ?? null,
            'codes' => $codes,
            'missing' => $missing,
            'headings' => $headings,
            'html' => $html,
        ];
    }

    /**
     * 只查一次資料庫；查不到（例如遷移尚未跑）就當作全部未知，頁面仍可開啟。
     *
     * @param  array<string, array{path: string, mtime: int}>  $sources
     * @return array<int, string>
     */
    private function knownReferenceCodes(array $sources): array
    {
        $codes = [];

        foreach ($sources as $source) {
            $raw = (string) file_get_contents($source['path']);

            foreach ($this->referenceCodes($raw) as $code) {
                $codes[$code] = true;
            }
        }

        if ($codes === []) {
            return [];
        }

        try {
            return Reference::query()->whereIn('code', array_keys($codes))->pluck('code')->all();
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function splitFrontMatter(string $raw): array
    {
        if (! str_starts_with($raw, '---')) {
            return [[], $raw];
        }

        $parts = preg_split('/^---$/m', $raw, 3);

        if ($parts === false || count($parts) < 3) {
            return [[], $raw];
        }

        $front = [];

        foreach (preg_split('/\r?\n/', trim($parts[1])) ?: [] as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = explode(':', $line, 2);
            $front[trim($key)] = trim($value);
        }

        return [$front, $parts[2]];
    }

    /**
     * @param  array<int, string>  $known
     * @return array{0: string, 1: array<int, array{id: string, text: string}>}
     */
    private function render(MarkdownConverter $converter, string $markdown, array $known): array
    {
        $html = $converter->convert($markdown)->getContent();

        // 已知的 [R-xx] 連到參考資料頁；未知的保留成純文字
        $html = preg_replace_callback(
            '/\[(R-\d{2})\]/',
            function (array $m) use ($known): string {
                if (! in_array($m[1], $known, true)) {
                    return '['.$m[1].']';
                }

                return '<a class="ref" href="'.route('learn.references').'#'.$m[1].'">['.$m[1].']</a>';
            },
            $html,
        ) ?? $html;

        preg_match_all('#<h2[^>]*>(.*?)</h2>#s', $html, $matches);

        $headings = [];

        foreach ($matches[1] as $index => $text) {
            $headings[] = ['id' => 'section-'.($index + 1), 'text' => trim(strip_tags($text))];
        }

        $html = preg_replace('#<h2#', '<h2 id="PLACEHOLDER"', $html) ?? $html;

        foreach ($headings as $heading) {
            $html = preg_replace('/id="PLACEHOLDER"/', 'id="'.$heading['id'].'"', $html, 1) ?? $html;
        }

        return [$html, $headings];
    }

    /** @return array<int, string> */
    private function referenceCodes(string $markdown): array
    {
        preg_match_all('/\[(R-\d{2})\]/', $markdown, $matches);

        return array_values(array_unique($matches[1]));
    }

    private function converter(): MarkdownConverter
    {
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);

        return new MarkdownConverter($environment);
    }
}
