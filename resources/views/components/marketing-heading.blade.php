@props([
    'eyebrow' => null,
    'title',
    'lead' => null,
    'align' => 'left',   // left | center
])

<div class="{{ $align === 'center' ? 'text-center max-w-2xl mx-auto' : 'max-w-2xl' }} mb-14">
    @if ($eyebrow)
        <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-4">{{ $eyebrow }}</p>
    @endif

    {{-- Heading/body contrast was modest; a wider ratio is the cheapest change
         that reads as designed rather than default. --}}
    <h2 class="text-4xl sm:text-5xl font-medium text-ink tracking-tight leading-[1.05]">{{ $title }}</h2>

    @if ($lead)
        <p class="text-lg text-muted mt-5 leading-relaxed">{{ $lead }}</p>
    @endif
</div>
