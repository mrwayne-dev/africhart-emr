@props([
    'tier',              // array from MarketingController::tiers()
    'compact' => false,  // homepage teaser hides the feature checklist
])

{{--
    Composition follows the reference: name → price → blurb → CTA → rule →
    checklist. The reference is dark with a yellow CTA; ours is a white card
    on warm with the standard ink pill. The featured tier is marked with an
    ink border instead of a lighter surface, since we have no dark surface.
--}}
<div @class([
    'bg-page rounded-card p-6 flex flex-col',
    'border-2 border-ink' => $tier['featured'],
    'border border-line' => ! $tier['featured'],
])>
    <div class="flex items-center justify-between gap-3">
        <h3 class="text-sm font-medium text-muted">{{ $tier['name'] }}</h3>
        @if ($tier['featured'])
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-ink text-white">
                Most popular
            </span>
        @endif
    </div>

    <p class="mt-4">
        <span class="text-3xl font-medium text-ink tracking-tight">&#8358;{{ $tier['price'] }}</span>
        <span class="text-sm text-muted">/month</span>
    </p>

    <p class="text-sm text-muted mt-3 leading-relaxed">{{ $tier['blurb'] }}</p>

    <p class="text-xs text-muted mt-3">
        One-time setup fee &#8358;{{ $tier['setup'] }}
    </p>

    <a href="{{ $tier['cta'] === 'Talk to us' ? route('demo') : route('signup') }}"
        @class([
            'rounded-full px-5 py-2.5 text-sm font-medium transition-colors text-center mt-6',
            'bg-ink text-white hover:bg-ink/90' => $tier['featured'],
            'border border-line text-ink hover:bg-warm' => ! $tier['featured'],
        ])>
        {{ $tier['cta'] }}
    </a>

    @unless ($compact)
        <ul class="flex flex-col gap-3 mt-6 pt-6 border-t border-line">
            @foreach ($tier['features'] as $feature)
                <li class="flex items-start gap-2.5 text-sm">
                    @if ($feature['included'])
                        <x-phosphor-check class="w-4 h-4 text-ink shrink-0 mt-0.5" aria-hidden="true" />
                        <span class="text-ink-body">{{ $feature['label'] }}</span>
                    @else
                        <x-phosphor-minus class="w-4 h-4 text-line shrink-0 mt-0.5" aria-hidden="true" />
                        <span class="text-muted line-through decoration-line">{{ $feature['label'] }}</span>
                        <span class="sr-only">not included</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endunless
</div>
