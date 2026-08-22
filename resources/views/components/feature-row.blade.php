@props([
    'id',
    'index',
    'title',
    'visual',              // key passed to marketing.partials.showcase-visual
    'points' => [],
    'flip' => false,       // put the visual on the left
])

{{--
    One feature, as an asymmetric two-column row. The side flips every other row
    so six of these read as a rhythm rather than six identical slabs — the
    failure mode diagnosed on Home's original feature grid.

    scroll-mt-24 clears the sticky 4rem nav when the page is reached via one of
    the nav dropdown's anchor links.
--}}
<div id="{{ $id }}" class="scroll-mt-24 grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">

    <div @class(['lg:order-2' => $flip]) data-reveal>
        <p class="font-mono text-xs text-muted uppercase tracking-[0.15em] mb-5">
            [ {{ str_pad((string) $index, 2, '0', STR_PAD_LEFT) }} ]
        </p>

        <h2 class="text-3xl sm:text-4xl font-medium text-ink tracking-tight leading-[1.05]">{{ $title }}</h2>

        <p class="text-lg text-muted mt-5 leading-relaxed">{{ $slot }}</p>

        @if ($points)
            <ul class="flex flex-col gap-3 mt-7">
                @foreach ($points as $point)
                    <li class="flex items-start gap-2.5">
                        <x-phosphor-check-circle class="w-[18px] h-[18px] text-ink shrink-0 mt-0.5" aria-hidden="true" />
                        <span class="text-sm text-ink-body leading-snug">{{ $point }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div @class(['lg:order-1' => $flip]) data-reveal data-reveal-delay="80">
        @include('marketing.partials.showcase-visual', ['key' => $visual])
    </div>
</div>
