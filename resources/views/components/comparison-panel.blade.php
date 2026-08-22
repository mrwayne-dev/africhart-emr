@props([
    'themLabel',        // the category we compare against — never a company name
    'usLabel',
    'rows' => [],       // [['them' => …, 'us' => …], …]
])

{{--
    Differentiator panel.

    Architecture assembled from three references:

    - Supply/Aspen (landingfolio.com/inspiration/post/supply) — two columns, ours
      legible and theirs recessed. Crucially the reference compares against a
      CATEGORY ("Plug & Play Widgets"), not a named product. We do the same:
      "Software built elsewhere", never a competitor's name. We cannot verify a
      claim about a specific rival, and a clinic owner can check every line here
      against us instead.
    - MemSQL (…/post/memsql) — the halves live inside ONE panel with a hard rule
      at the seam, rather than floating as two separate cards.
    - Nightwatch (…/post/nightwatch) — state what we do, then say quietly what the
      alternative does. The contrast is carried by the copy, not by mockery.

    Surfaces are the two already in the system: bg-warm (recessed) and bg-ink,
    the same ink treatment as feature-panel. No new tone, no new token.
--}}
<div class="rounded-card overflow-hidden border border-line" data-reveal>
    <div class="grid lg:grid-cols-2">

        {{-- The alternative. Recessed: warm surface, muted text, a neutral dash
             rather than a cross — this is a comparison, not a verdict. --}}
        <div class="bg-warm p-7 sm:p-10 lg:p-12">
            <p class="font-mono text-xs text-muted uppercase tracking-[0.15em]">{{ $themLabel }}</p>

            <ul class="mt-8 flex flex-col gap-5">
                @foreach ($rows as $row)
                    <li class="flex items-start gap-3.5">
                        <x-phosphor-minus class="w-4 h-4 text-muted/50 shrink-0 mt-0.5" aria-hidden="true" />
                        <span class="text-sm sm:text-base text-muted leading-relaxed">{{ $row['them'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Accent 3 of 3 on this page: the seam.

             It is a BORDER on the ink half, not a third grid child — a divider
             element inside a 2-column grid would be laid out as a third column
             and break the split. As a border it also flips for free: a rule
             above the panel when stacked, to its left once side by side. --}}
        <div class="bg-ink p-7 sm:p-10 lg:p-12 border-t lg:border-t-0 lg:border-l border-accent">
            <p class="font-mono text-xs text-white/45 uppercase tracking-[0.15em]">{{ $usLabel }}</p>

            <ul class="mt-8 flex flex-col gap-5">
                @foreach ($rows as $row)
                    <li class="flex items-start gap-3.5">
                        <x-phosphor-check class="w-4 h-4 text-white shrink-0 mt-0.5" aria-hidden="true" />
                        <span class="text-sm sm:text-base text-white/85 leading-relaxed">{{ $row['us'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
