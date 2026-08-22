@props(['id', 'index', 'title', 'cards' => []])

{{--
    Architecture from the top of the customer.io integrations block: a centred
    section header, then two equal cards each carrying a visual above an
    icon, title and body.
--}}
<div id="{{ $id }}" class="scroll-mt-24">
    <div class="max-w-2xl mx-auto text-center mb-14" data-reveal>
        <p class="font-mono text-xs text-muted uppercase tracking-[0.15em] mb-5">
            [ {{ str_pad((string) $index, 2, '0', STR_PAD_LEFT) }} ]
        </p>
        <h2 class="text-3xl sm:text-4xl font-medium text-ink tracking-tight leading-[1.05]">{{ $title }}</h2>
        <p class="text-lg text-muted mt-5 leading-relaxed">{{ $slot }}</p>
    </div>

    <div class="grid md:grid-cols-2 gap-5">
        @foreach ($cards as $i => $card)
            <div class="bg-warm border border-line rounded-card p-6 sm:p-8 flex flex-col
                    transition-all duration-200 hover:-translate-y-0.5 hover:border-muted/30"
                data-reveal data-reveal-delay="{{ $i * 80 }}">
                <div class="mb-8">
                    @include('marketing.partials.showcase-visual', ['key' => $card['visual']])
                </div>
                <div class="mt-auto">
                    <x-dynamic-component :component="$card['icon']" class="w-5 h-5 text-ink mb-4" aria-hidden="true" />
                    <h3 class="text-lg font-medium text-ink tracking-tight">{{ $card['title'] }}</h3>
                    <p class="text-sm text-muted mt-2 leading-relaxed">{{ $card['body'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>
