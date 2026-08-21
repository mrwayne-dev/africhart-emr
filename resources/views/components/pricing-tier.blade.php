@props([
    'tier',             // array from MarketingController::tiers()
    'size' => 'md',     // md = standard card · lg = the featured hero card
])

@php $lg = $size === 'lg'; @endphp

{{--
    Composition follows the pricing reference: plan name → large price →
    one-line blurb → full-width CTA with arrow → divider → tight check list.

    The `lg` variant turns the card on its side for the featured plan: the offer
    sits on the left, the checklist runs in two columns on the right, so one
    plan can carry the section on its own without a row of competing cards.

    Reference is near-black with a yellow CTA; ours stays light, with the
    featured tier marked by an ink border (we have no dark surface to lighten).
--}}
<div @class([
    'group bg-page rounded-card transition-all duration-200',
    'border-2 border-ink p-6 sm:p-10' => $tier['featured'],
    'border border-line hover:border-muted/30 hover:-translate-y-0.5 p-6 sm:p-7' => ! $tier['featured'],
])>
    <div @class(['grid lg:grid-cols-2 lg:gap-12 items-start' => $lg])>

        {{-- Offer --}}
        <div class="flex flex-col">
            <div class="flex items-center justify-between gap-3 min-h-[1.75rem]">
                <h3 class="text-sm font-medium text-muted">{{ $tier['name'] }}</h3>
                @if ($tier['featured'])
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-ink text-white">
                        Most popular
                    </span>
                @endif
            </div>

            <p class="mt-4 flex items-baseline gap-1.5">
                <span @class([
                    'font-medium text-ink tracking-tight',
                    'text-5xl sm:text-6xl' => $lg,
                    'text-4xl' => ! $lg,
                ])>&#8358;{{ $tier['price'] }}</span>
                <span class="text-sm text-muted">/month</span>
            </p>

            <p @class(['text-muted mt-3 leading-relaxed', 'text-base' => $lg, 'text-sm' => ! $lg])>
                {{ $tier['blurb'] }}
            </p>

            <a href="{{ $tier['cta'] === 'Talk to us' ? route('demo') : route('signup') }}"
                @class([
                    'group/cta inline-flex items-center justify-center gap-1.5 w-full rounded-full text-sm font-medium transition-colors mt-6',
                    'px-6 py-3.5' => $lg,
                    'px-5 py-3' => ! $lg,
                    'bg-ink text-white hover:bg-ink/90' => $tier['featured'],
                    'bg-warm text-ink hover:bg-line' => ! $tier['featured'],
                ])>
                {{ $tier['cta'] }}
                <x-phosphor-arrow-right class="w-4 h-4 transition-transform group-hover/cta:translate-x-0.5" aria-hidden="true" />
            </a>

            <p class="text-xs text-muted mt-3 text-center">
                One-time setup fee &#8358;{{ $tier['setup'] }}
            </p>
        </div>

        {{-- Checklist. On the lg card it becomes the right-hand column and needs
             no top divider; on the standard card it sits below one. --}}
        <ul @class([
            'flex flex-col gap-3',
            'sm:grid sm:grid-cols-2 sm:gap-x-8 lg:mt-0 mt-8 lg:border-l lg:border-line lg:pl-12 lg:h-full' => $lg,
            'mt-6 pt-6 border-t border-line' => ! $lg,
        ])>
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
    </div>
</div>
