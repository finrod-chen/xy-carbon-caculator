<?php

namespace App\Support\Content;

/** 一個說明頁（resources/content 下的 Markdown 檔）。 */
final class Page
{
    /**
     * @param  array<int, string>  $referenceCodes
     * @param  array<int, array{id: string, text: string}>  $headings
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly int $order,
        public readonly ?string $owner,
        public readonly ?string $updated,
        public readonly array $referenceCodes,
        public readonly array $headings,
        public readonly string $html,
    ) {}
}
