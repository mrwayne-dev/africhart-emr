@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'optional' => false,
    'prefix' => null,
    'options' => null,
    'readonly' => false,
    'rows' => null,
])

@php
    $id = $attributes->get('id', $name);

    /*
     * old() wins, then the passed value. Written out rather than
     * old($name, $value) so a field whose value is legitimately '0' — a
     * consultation fee of zero, say — is not swallowed by a loose falsy check.
     */
    $current = old($name, $value);

    /*
     * The input classes are lifted VERBATIM from the existing app forms
     * (drug-catalog/index.blade.php, the queue and vitals modals). This
     * component exists to stop that string being retyped once per field across
     * five settings screens — it is not a new style, and it must not become
     * one. Change the look here and it changes everywhere, which is the point.
     */
    $base = 'w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
             focus:bg-page focus:border-ink focus:outline-none transition-colors';

    // Read-only fields state their inertness visually as well as with the
    // attribute — a field that looks editable and silently is not reads as a
    // bug rather than a rule.
    $readonlyClasses = 'bg-warm/60 text-muted cursor-not-allowed';
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-ink-body mb-2">
        {{ $label }}
        @if ($optional)
            <span class="text-muted font-normal">(optional)</span>
        @endif
    </label>

    @if ($options !== null)
        <select name="{{ $name }}" id="{{ $id }}"
            @disabled($readonly)
            {{ $attributes->merge(['class' => $base.($readonly ? ' '.$readonlyClasses : '')]) }}>
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @elseif ($rows !== null)
        <textarea name="{{ $name }}" id="{{ $id }}" rows="{{ $rows }}"
            @readonly($readonly)
            {{ $attributes->merge(['class' => $base.($readonly ? ' '.$readonlyClasses : '')]) }}>{{ $current }}</textarea>
    @else
        <div class="relative">
            @if ($prefix)
                {{-- Currency and similar. Sits inside the field rather than
                     beside it so the control still reads as one thing. --}}
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-sm text-muted pointer-events-none">{{ $prefix }}</span>
            @endif

            <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}" value="{{ $current }}"
                @readonly($readonly)
                {{ $attributes->merge(['class' => $base.($prefix ? ' pl-9' : '').($readonly ? ' '.$readonlyClasses : '')]) }}>
        </div>
    @endif

    @error($name)
        {{-- Inline, under the field it belongs to. `role=alert` so a screen
             reader announces it when validation bounces the form back. --}}
        <p class="mt-2 text-sm text-accent" role="alert">{{ $message }}</p>
    @enderror

    @if ($hint)
        <p class="mt-2 text-xs text-muted leading-relaxed">{{ $hint }}</p>
    @endif
</div>
