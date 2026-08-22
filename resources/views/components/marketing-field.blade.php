@props([
    'name',
    'label',
    'type' => 'text',
    'optional' => false,
    'placeholder' => null,
    'value' => null,
    'autocomplete' => null,
    'rows' => null,       // set to render a textarea
    'inputmode' => null,
])

{{--
    Form field for the marketing pages.

    Deliberately the EMR's own input idiom — bg-warm resting, transparent border,
    flipping to bg-page + border-ink on focus, label above, @error below in
    text-accent. A visitor who signs up and later uses the app should meet the
    same form controls in both places.
--}}
<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-ink-body mb-2">
        {{ $label }}
        @if ($optional)
            <span class="text-muted font-normal">(optional)</span>
        @endif
    </label>

    @php
        $classes = 'w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
            focus:bg-page focus:border-ink focus:outline-none transition-colors';
    @endphp

    @if ($rows)
        <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @unless ($optional) required @endunless
            class="{{ $classes }}">{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
            value="{{ old($name, $value) }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($inputmode) inputmode="{{ $inputmode }}" @endif
            @unless ($optional) required @endunless
            class="{{ $classes }}">
    @endif

    @error($name)
        <p class="mt-2 text-sm text-accent">{{ $message }}</p>
    @enderror
</div>
