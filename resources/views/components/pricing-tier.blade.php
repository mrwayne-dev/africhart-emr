@props([
    'tier',              // array from MarketingController::tiers()
    'compact' => false,  // hides the checklist — avoid: the list IS the substance
])

{{--
    Composition follows the pricing reference exactly: plan name → large price →
    one-line blurb → FULL-WIDTH CTA with arrow → divider → tight check list.

    The reference is near-black with a yellow CTA; ours keeps the site's light
    palette, with the featured tier marked by an ink border rather than a
    lighter surface (we have no dark surface to lighten against).
--}}
<div @class([
    'group bg-page rounded-card p-6 sm:p-7 flex flex-col transition-all duration-200 hover:-translate-y-0.5',
    'border-2 border-ink' => $tier['featured'],
    'border border-line hover:border-muted/30' => ! $tier['featured'],
])>
    <div class="flex items-center justify-between gap-3 min-h-[1.75rem]">
        <h3 class="text-sm font-medium text-muted">{{ $tier['name'] }}</h3>
        @if ($tier['featured'])
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-ink text-white">
                Most popular
            </span>
        @endif
    </div>

    <p class="mt-4 flex items-baseline gap-1.5">
        <span class="text-4xl font-medium text-ink tracking-tight">&#8358;{{ $tier['price'] }}</span>
        <span class="text-sm text-muted">/month</span>
    </p>

    <p class="text-sm text-muted mt-3 leading-relaxed">{{ $tier['blurb'] }}</p>

    <a href="{{ $tier['cta'] === 'Talk to us' ? route('demo') : route('signup') }}"
        @class([
            'inline-flex items-center justify-center gap-1.5 w-full rounded-full px-5 py-3 text-sm font-medium transition-colors mt-6',
            'bg-ink text-white hover:bg-ink/90' => $tier['featured'],
            'bg-warm text-ink hover:bg-line' => ! $tier['featured'],
        ])>
        {{ $tier['cta'] }}
        <x-phosphor-arrow-right class="w-4 h-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
    </a>

    <p class="text-xs text-muted mt-3 text-center">
        One-time setup fee &#8358;{{ $tier['setup'] }}
    </p>

    @unless ($compact)
        <ul class="flex flex-col gap-3 mt-6 pt-6 border-t border-line">
            @foreach ($tier['features'] as $feature)
                <li class="flex items-start gap-2.5 text-sm">
                    @if ($feature['included'])
                        <x-phosphor-check-circle class="w-[18px] h-[18px] text-ink shrink-0 mt-px" aria-hidden="true" />
                        <span class="text-ink-body leading-snug">{{ $feature['label'] }}</span>
                    @else
                        <x-phosphor-minus-circle class="w-[18px] h-[18px] text-line shrink-0 mt-px" aria-hidden="true" />
                        <span class="text-muted leading-snug">{{ $feature['label'] }}</span>
                        <span class="sr-only">not included</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endunless
</div>
