@props([
    'title' => null,
    'description' => null,
])

<section {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title)
        <h2 style="margin-top:0">{{ $title }}</h2>
    @endif
    @if ($description)
        <p class="hint">{{ $description }}</p>
    @endif

    {{ $slot }}
</section>
