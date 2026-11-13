@props([
    'title' => '尚無資料',
    'description' => null,
])

<div class="card" style="text-align:center;color:#6b7280">
    <p style="margin:0 0 4px;font-weight:600;color:#1f2328">{{ $title }}</p>
    @if ($description)
        <p class="hint" style="margin:0">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
