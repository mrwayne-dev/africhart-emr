@props(['stats'])

{{--
    Product facts, not traction metrics.

    AfriChart has no clinics signed, so there is no "500+ clinics" to show. Every
    number here is true today and checkable against the product. Do not swap
    these for performance figures until the figures exist — the buyer who checks
    is exactly the buyer worth having.

    Each numeral renders at its TRUE value in the markup; countUp animates toward
    it. With no JS, a slow connection, or reduced motion, the correct number is
    already on screen.

    Plain divs rather than dl/dt/dd: the first version put the label in an
    sr-only <dt> AND again in the visible text, so a screen reader read every
    label twice. Putting <dd> before <dt> to fix the visual order would be
    invalid HTML, so the list semantics are dropped — they bought little here.
--}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-10">
    @foreach ($stats as $i => $stat)
        <div data-reveal data-reveal-delay="{{ $i * 80 }}">
            {{-- tabular-nums so the glyphs are fixed-width: the box cannot
                 resize as digits change, so counting causes no layout shift. --}}
            <p class="text-5xl sm:text-6xl font-medium text-ink tracking-tight tabular-nums"
                x-data="countUp({{ (int) $stat['value'] }})"
                x-text="display">{{ $stat['value'] }}</p>

            <p class="text-sm text-muted mt-3 leading-snug max-w-[14rem]">{{ $stat['label'] }}</p>
        </div>
    @endforeach
</div>
