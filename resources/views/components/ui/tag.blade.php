@props([
    'tone' => 'ok',
    'label' => null,
])

{{-- 狀態一律有文字，不只靠顏色（WP0.6 驗收） --}}
<span {{ $attributes->merge(['class' => 'tag'.($tone === 'ok' ? '' : ' off')]) }}>{{ $label ?? $slot }}</span>
