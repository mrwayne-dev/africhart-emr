@props([
    'title',
    'icon' => 'phosphor-check',
    'tall' => false,   // large bento cells get more padding
])

{{--
    Bento cell. In iteration 1 this was a borderless cell inside a hairline grid;
    the bento layout uses gaps instead, so the card now carries its own border.

    Default slot = the description copy. Optional `visual` slot = a mock or
    illustration for the large cells.

    Hover lifts 2px and darkens the border — depth from contrast, never a shadow.
--}}
<div {{ $attributes->merge([
    'class' => 'group bg-page border border-line rounded-card flex flex-col
        transition-all duration-300 hover:-translate-y-0.5 hover:border-muted/30 '
        .($tall ? 'p-6 sm:p-8' : 'p-6 sm:p-7'),
]) }}>
    <span class="bg-warm border border-line rounded-card p-2.5 w-fit transition-colors group-hover:bg-page">
        <x-dynamic-component :component="$icon" class="w-5 h-5 text-ink" aria-hidden="true" />
    </span>

    <h3 class="text-base font-medium text-ink tracking-tight mt-5">{{ $title }}</h3>

    <p class="text-sm text-muted mt-2 leading-relaxed">{{ $slot }}</p>

    @isset($visual)
        <div class="mt-6 flex-1 flex items-end">{{ $visual }}</div>
    @endisset
</div>
