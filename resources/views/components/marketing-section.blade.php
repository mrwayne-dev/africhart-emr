@props([
    'tone' => 'page',   // page | warm | warm-alt — sections alternate for depth
    'tight' => false,   // reduced vertical padding for narrow strips
])

@php
    /*
     * Literal class strings, not "bg-{$tone}". Tailwind 4 scans source text for
     * complete class names, so a concatenated class is never generated — and
     * bg-warm-alt appears nowhere else in the codebase, so it would silently
     * render as no background at all.
     */
    $toneClass = match ($tone) {
        'warm' => 'bg-warm',
        'warm-alt' => 'bg-warm-alt',
        default => 'bg-page',
    };
    $padClass = $tight ? 'py-10 sm:py-12' : 'py-16 sm:py-24';
@endphp

<section {{ $attributes->merge(['class' => $toneClass.' '.$padClass]) }}>
    <div class="max-w-5xl mx-auto px-6 sm:px-8">
        {{ $slot }}
    </div>
</section>
