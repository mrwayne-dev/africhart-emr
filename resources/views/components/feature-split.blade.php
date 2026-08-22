@props(['id', 'index', 'title', 'visual', 'items' => [], 'flip' => false])

{{--
    Architecture from the Design Studio reference: a large visual on one side,
    and on the other an eyebrow, heading, body, then a STRUCTURED ICON LIST —
    small square chip, bold label, one line of explanation.

    That list is the point of this layout. Plain check-bullets read as filler;
    an icon list with a label and a reason reads as considered, and it scans
    far faster than a paragraph.
--}}
<div id="{{ $id }}" class="scroll-mt-24 grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">

    <div @class(['lg:order-2' => $flip]) data-reveal>
        <p class="font-mono text-xs text-muted uppercase tracking-[0.15em] mb-5">
            [ {{ str_pad((string) $index, 2, '0', STR_PAD_LEFT) }} ]
        </p>

        <h2 class="text-3xl sm:text-4xl font-medium text-ink tracking-tight leading-[1.05]">{{ $title }}</h2>
        <p class="text-lg text-muted mt-5 leading-relaxed">{{ $slot }}</p>

        <ul class="flex flex-col gap-6 mt-9">
            @foreach ($items as $item)
                <li class="flex items-start gap-4">
                    <span class="bg-warm border border-line rounded-card p-2 shrink-0">
                        <x-dynamic-component :component="$item['icon']" class="w-4 h-4 text-ink" aria-hidden="true" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-medium text-ink">{{ $item['title'] }}</span>
                        <span class="block text-sm text-muted mt-1 leading-relaxed">{{ $item['body'] }}</span>
                    </span>
                </li>
            @endforeach
        </ul>
    </div>

    <div @class(['lg:order-1' => $flip]) data-reveal data-reveal-delay="80">
        @include('marketing.partials.showcase-visual', ['key' => $visual])
    </div>
</div>
