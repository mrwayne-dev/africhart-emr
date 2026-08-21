@props([
    'eyebrow' => null,
    'title',
    'lead' => null,
    'align' => 'left',   // left | center
])

<div class="{{ $align === 'center' ? 'text-center max-w-2xl mx-auto' : 'max-w-2xl' }} mb-12">
    @if ($eyebrow)
        <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-4">{{ $eyebrow }}</p>
    @endif

    <h2 class="text-3xl sm:text-4xl font-medium text-ink tracking-tight leading-tight">{{ $title }}</h2>

    @if ($lead)
        <p class="text-base text-muted mt-4 leading-relaxed">{{ $lead }}</p>
    @endif
</div>
