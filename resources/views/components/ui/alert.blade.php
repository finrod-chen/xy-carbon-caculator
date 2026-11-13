@props([
    'tone' => 'info',
    'role' => null,
])

@php
    $toneClass = match ($tone) {
        'ok' => 'ok',
        'error' => 'error',
        default => 'info',
    };
    $roleAttr = $role ?? ($tone === 'error' ? 'alert' : 'status');
@endphp

<div {{ $attributes->merge(['class' => 'alert '.$toneClass, 'role' => $roleAttr]) }}>{{ $slot }}</div>
