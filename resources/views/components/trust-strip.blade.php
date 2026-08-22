@props(['items'])

{{--
    Inline capability strip — hairline-separated, not a grid of tiles.

    Replaces the 5-tile grid from the previous pass: the page had a card grid in
    every section, and this one carried the least information per unit of space.

    IMPORTANT — the reference this derives from is a wall of certification badges
    (GDPR, HIPAA, ISO 27001, SOC 2). AfriChart holds none of those. Every entry
    here states a capability we can actually demonstrate. Do not swap these for
    certification marks unless the certificate exists: clinic owners read this
    when deciding whether to trust us with patient records.
--}}
<div class="flex flex-col sm:flex-row sm:flex-wrap border-t border-b border-line divide-y sm:divide-y-0 sm:divide-x divide-line">
    @foreach ($items as $i => $item)
        <div class="flex items-center gap-3 py-5 sm:px-6 sm:first:pl-0 sm:last:pr-0 flex-1 min-w-[13rem]"
            data-reveal data-reveal-delay="{{ $i * 100 }}">
            <x-dynamic-component :component="$item['icon']" class="w-5 h-5 text-ink shrink-0" aria-hidden="true" />
            <div class="min-w-0">
                <p class="text-sm font-medium text-ink leading-tight">{{ $item['name'] }}</p>
                <p class="text-xs text-muted mt-0.5">{{ $item['sub'] }}</p>
            </div>
        </div>
    @endforeach
</div>
