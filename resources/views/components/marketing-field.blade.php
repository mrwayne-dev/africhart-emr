@props([
    'name',
    'label',
    'type' => 'text',
    'optional' => false,
    'placeholder' => null,
    'value' => null,
    'autocomplete' => null,
    'rows' => null,       // set to render a textarea
    'options' => null,    // ['value' => 'Label'] — set to render a select
    'inputmode' => null,
    'hint' => null,
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

    @if ($options)
        {{-- Native select on purpose. A custom listbox would be a second
             interaction pattern to build and keep accessible for no gain, and
             on a phone the OS picker beats anything we would write. --}}
        <select id="{{ $name }}" name="{{ $name }}"
            @unless ($optional) required @endunless
            {{ $attributes }}
            class="{{ $classes }} appearance-none bg-[length:1rem] bg-[right_1rem_center] bg-no-repeat pr-10"
            style="background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='%23636363' stroke-width='1.5'%3E%3Cpath d='M4 6l4 4 4-4'/%3E%3C/svg%3E&quot;)">
            <option value="">{{ $placeholder ?? 'Select…' }}</option>
            @foreach ($options as $optValue => $optLabel)
                <option value="{{ $optValue }}" @selected(old($name, $value) === (string) $optValue)>{{ $optLabel }}</option>
            @endforeach
        </select>
    @elseif ($rows)
        <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @unless ($optional) required @endunless
            {{ $attributes }}
            class="{{ $classes }}">{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
            value="{{ old($name, $value) }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($inputmode) inputmode="{{ $inputmode }}" @endif
            @unless ($optional) required @endunless
            {{ $attributes }}
            class="{{ $classes }}">
    @endif

    @if ($hint)
        <p class="mt-2 text-xs text-muted">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-2 text-sm text-accent">{{ $message }}</p>
    @enderror
</div>
