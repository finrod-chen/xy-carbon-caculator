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

@if ($toneClass === 'info')
    <style>
        .alert.info { background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; }
    </style>
@endif

<div {{ $attributes->merge(['class' => 'alert '.$toneClass, 'role' => $roleAttr]) }}>{{ $slot }}</div>
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
