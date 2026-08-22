@props(['id', 'index', 'title', 'visual', 'notes' => [], 'flip' => false])

{{--
    Architecture from the customer.io "Security you can trust" card: one
    full-width tinted panel with an internal split, and two icon/text notes
    sitting along the bottom of the text column.

    Rendered on ink rather than the reference's teal/maroon — the palette stays
    ours, only the composition is borrowed. Used sparingly: two per page, on the
    features that carry the most weight, so it stays a moment rather than a
    pattern.
--}}
<div id="{{ $id }}" class="scroll-mt-24 bg-ink rounded-card overflow-hidden" data-reveal>
    <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center p-7 sm:p-12 lg:p-16">

        <div @class(['lg:order-2' => $flip])>
            <p class="font-mono text-xs text-white/45 uppercase tracking-[0.15em] mb-5">
                [ {{ str_pad((string) $index, 2, '0', STR_PAD_LEFT) }} ]
            </p>

            <h2 class="text-3xl sm:text-4xl font-medium text-white tracking-tight leading-[1.05]">{{ $title }}</h2>
            <p class="text-lg text-white/65 mt-5 leading-relaxed">{{ $slot }}</p>

            @if ($notes)
                <div class="grid sm:grid-cols-2 gap-6 mt-10 pt-8 border-t border-white/10">
                    @foreach ($notes as $note)
                        <div>
                            <x-dynamic-component :component="$note['icon']" class="w-5 h-5 text-white mb-3" aria-hidden="true" />
                            <p class="text-sm text-white/65 leading-relaxed">{{ $note['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- The visual is a light card, so it reads as a lit screen against the
             dark panel — the contrast the reference gets from its screenshots. --}}
        <div @class(['lg:order-1' => $flip])>
            @include('marketing.partials.showcase-visual', ['key' => $visual])
        </div>
    </div>
</div>
