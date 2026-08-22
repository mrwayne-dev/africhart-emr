@props(['title', 'updated', 'summary', 'sections'])

{{--
    Shared shell for the three legal documents.

    Carries a visible "pending professional legal review" notice. SOW §9.3 makes
    legal certification the Client's responsibility and the Developer's job the
    technical implementation — that distinction belongs on the page itself, not
    only in the contract, so nobody mistakes these for reviewed documents.
--}}
<x-marketing-section tone="page">
    <div class="max-w-3xl">
        <p class="font-mono text-xs text-muted uppercase tracking-[0.2em] mb-6" data-reveal>
            <span class="text-accent">[</span> Legal <span class="text-accent">]</span>
        </p>

        <h1 class="text-4xl sm:text-5xl font-medium text-ink tracking-tight leading-[1.03]"
            data-reveal data-reveal-delay="80">{{ $title }}</h1>

        <p class="text-lg text-muted mt-6 leading-relaxed" data-reveal data-reveal-delay="160">{{ $summary }}</p>

        <p class="font-mono text-xs text-muted uppercase tracking-wide mt-8" data-reveal data-reveal-delay="200">
            Last updated {{ $updated }}
        </p>

        <div class="border border-line rounded-card p-5 mt-8 flex items-start gap-3.5"
            data-reveal data-reveal-delay="240" role="note">
            <x-phosphor-info class="w-5 h-5 text-ink shrink-0 mt-0.5" aria-hidden="true" />
            <p class="text-sm text-muted leading-relaxed">
                <span class="text-ink font-medium">Pending professional legal review.</span>
                This document sets out how AfriChart actually operates today and is published in
                good faith, but it has not yet been reviewed by a qualified lawyer. If you are
                relying on it for a compliance decision, ask us for the reviewed version first.
            </p>
        </div>
    </div>
</x-marketing-section>

<x-marketing-section tone="warm">
    <div class="grid lg:grid-cols-12 gap-10 lg:gap-16">

        {{-- Section index. Sticky on desktop so a long document stays navigable. --}}
        <nav class="lg:col-span-3" aria-label="On this page">
            <div class="lg:sticky lg:top-24">
                <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-4">On this page</p>
                <ul class="flex flex-col gap-2.5">
                    @foreach ($sections as $i => $section)
                        <li>
                            <a href="#{{ Str::slug($section['heading']) }}"
                                class="text-sm text-muted hover:text-ink transition-colors">
                                {{ $i + 1 }}. {{ $section['heading'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </nav>

        <div class="lg:col-span-9 max-w-2xl">
            @foreach ($sections as $i => $section)
                <section id="{{ Str::slug($section['heading']) }}"
                    class="scroll-mt-24 py-8 first:pt-0 border-b border-line last:border-b-0"
                    data-reveal data-reveal-delay="{{ min($i, 4) * 60 }}">

                    <h2 class="text-xl font-medium text-ink tracking-tight">
                        <span class="font-mono text-xs text-muted mr-2">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        {{ $section['heading'] }}
                    </h2>

                    <div class="mt-4 space-y-4">
                        @foreach ($section['body'] as $para)
                            <p class="text-base text-muted leading-relaxed">{{ $para }}</p>
                        @endforeach

                        @if (! empty($section['list']))
                            <ul class="flex flex-col gap-2.5 pt-1">
                                @foreach ($section['list'] as $item)
                                    <li class="flex items-start gap-2.5">
                                        <span class="w-1 h-1 rounded-full bg-muted shrink-0 mt-2.5" aria-hidden="true"></span>
                                        <span class="text-base text-muted leading-relaxed">{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </section>
            @endforeach

            <div class="pt-10">
                <p class="text-sm text-muted">
                    Questions about this document? Email
                    <a href="mailto:hello@africhartemr.com" class="text-ink hover:underline">hello@africhartemr.com</a>.
                </p>
            </div>
        </div>
    </div>
</x-marketing-section>
