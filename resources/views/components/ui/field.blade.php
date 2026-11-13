@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
])

<label for="{{ $name }}">{{ $label }}</label>
<input
    id="{{ $name }}"
    name="{{ $name }}"
    type="{{ $type }}"
    value="{{ old($name, $value) }}"
    @if ($errors->has($name)) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
    {{ $attributes }}
>
@if ($hint)
    <p class="hint">{{ $hint }}</p>
@endif
@error($name)
    <p class="hint" id="{{ $name }}-error" style="color:#991b1b">{{ $message }}</p>
@enderror
