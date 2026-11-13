<?php

namespace App\Support\Content;

use App\Models\Reference;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

/**
 * 說明頁來源：resources/content/**\/*.md（PHASE0 WP0.7）。
 * 新增一個說明頁只需新增一個 Markdown 檔，不需要改程式。
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

    /** @return array<string, Page> */
    private function load(): array
    {
        $root = resource_path('content');

        if (! is_dir($root)) {
            return [];
        }

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        $converter = $this->converter();
        $pages = [];
        $sources = [];

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'md') {
                continue;
            }

            $relative = str_replace($root.DIRECTORY_SEPARATOR, '', $file->getPathname());
            $slug = str_replace('.md', '', str_replace(DIRECTORY_SEPARATOR, '/', $relative));

            [$front, $markdown] = $this->splitFrontMatter((string) file_get_contents($file->getPathname()));
            [$html, $headings] = $this->render($converter, $markdown);

            $sources[$slug] = [
                'front' => $front,
                'markdown' => $markdown,
                'html' => $html,
                'headings' => $headings,
            ];
        }

        $known = $this->knownReferenceCodes($sources);

        foreach ($sources as $slug => $source) {
            $codes = $this->referenceCodes($source['markdown']);

            $pages[$slug] = new Page(
                slug: $slug,
                title: (string) ($source['front']['title'] ?? $slug),
                order: (int) ($source['front']['order'] ?? 99),
                owner: $source['front']['owner'] ?? null,
                updated: $source['front']['updated'] ?? null,
                referenceCodes: $codes,
                missingReferences: array_values(array_diff($codes, $known)),
                headings: $source['headings'],
                html: $source['html'],
            );
        }

        uasort($pages, fn (Page $a, Page $b) => $a->order <=> $b->order ?: strcmp($a->title, $b->title));

        return $pages;
    }

    /**
     * 內容引用到不存在的 [R-xx] 時，頁面仍要開得起來，只顯示「待補」提示；
     * 真正的把關放在 CI（tests/Feature/ContentReferenceIntegrityTest.php）。
     *
     * @param  array<string, array{front: array<string, mixed>, markdown: string, html: string, headings: array<int, array{id: string, text: string}>}>  $sources
     * @return array<int, string>
     */
    private function knownReferenceCodes(array $sources): array
    {
        $codes = [];

        foreach ($sources as $source) {
            foreach ($this->referenceCodes($source['markdown']) as $code) {
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
     * @return array{0: string, 1: array<int, array{id: string, text: string}>}
     */
    private function render(MarkdownConverter $converter, string $markdown): array
    {
        $html = $converter->convert($markdown)->getContent();

        // [R-xx] 一律連到參考資料頁；未知編號已在上一步檢查
        $html = preg_replace_callback(
            '/\[(R-\d{2})\]/',
            fn (array $m) => '<a class="ref" href="'.route('learn.references').'#'.$m[1].'">['.$m[1].']</a>',
            $html,
        ) ?? $html;

        preg_match_all('#<h2[^>]*>(.*?)</h2>#s', $html, $matches);

        $headings = [];

        foreach ($matches[1] as $index => $text) {
            $id = 'section-'.($index + 1);
            $headings[] = ['id' => $id, 'text' => trim(strip_tags($text))];
        }

        $html = preg_replace('#<h2#', '<h2 id="PLACEHOLDER"', $html) ?? $html;

        foreach ($headings as $index => $heading) {
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
            'html_input' => 'strip',        // 內容中的原始 HTML 一律移除（XSS 防線）
            'allow_unsafe_links' => false,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);

        return new MarkdownConverter($environment);
    }
}
