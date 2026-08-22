@props([
    'tone' => 'page',   // page | warm | warm-alt
    'size' => 'md',     // tight | md | tall
])

@php
    /*
     * Literal class strings, not "bg-{$tone}". Tailwind 4 scans source text for
     * complete class names, so a concatenated class is never generated — and
     * bg-warm-alt appears nowhere else, so it would silently render no
     * background at all.
     */
    $toneClass = match ($tone) {
        'warm' => 'bg-warm',
        'warm-alt' => 'bg-warm-alt',
        default => 'bg-page',
    };

    /*
     * Sections are deliberately unequal. Every section previously used the same
     * padding, which meant nothing could be more important than anything else —
     * `tall` carries the two sections that do the selling, `tight` acts as a
     * breathing beat between them.
     */
    $padClass = match ($size) {
        'tight' => 'py-12 sm:py-16',
        'tall' => 'py-24 sm:py-36',
        default => 'py-16 sm:py-24',
    };
@endphp

<section {{ $attributes->merge(['class' => $toneClass.' '.$padClass]) }}>
    <div class="max-w-7xl mx-auto px-6 sm:px-8">
        {{ $slot }}
    </div>
</section>
