@props([
    'tone' => 'info',
    'role' => null,
])

@php
    $toneClass = in_array($tone, ['ok', 'error'], true) ? $tone : 'info';
    $roleAttr = $role ?? ($toneClass === 'error' ? 'alert' : 'status');
@endphp

@if ($toneClass === 'info')
    <style>
        .alert.info { background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; }
    </style>
@endif

<div {{ $attributes->merge(['class' => 'alert '.$toneClass, 'role' => $roleAttr]) }}>{{ $slot }}</div>
