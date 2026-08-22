@props([
    'eyebrow' => null,
    'title',
    'lead' => null,
    'steps' => [],   // [['label' => …, 'duration' => …, 'body' => …, 'points' => [...]], …]
])

{{--
    "What happens next" strip, shared by Book a Demo and Get Started.

    Two references, both structural:

    - Unikorns' workflow block (landingfolio.com/inspiration/post/unikorns):
      numbered pills heading columns joined by a hairline, each carrying its own
      sub-points. Already our idiom — we number with [ 01 ] and separate with
      hairlines everywhere else.
    - AirDev (…/post/airdev): every step carries a DURATION under its label
      ("1 week", "Ongoing"). That single addition is what turns a process
      diagram into a reassurance: the reader learns not just what happens but
      how long they are waiting, which is the actual anxiety.

    The numbering earns its place here because this is a genuine sequence —
    unlike a feature grid, where numbering is decoration.
--}}
<div>
    <div class="max-w-2xl" data-reveal>
        @if ($eyebrow)
            <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-4">{{ $eyebrow }}</p>
        @endif

        <h2 class="text-3xl sm:text-4xl font-medium text-ink tracking-tight leading-[1.05]">{{ $title }}</h2>

        @if ($lead)
            <p class="text-lg text-muted mt-5 leading-relaxed">{{ $lead }}</p>
        @endif
    </div>

    <div class="grid sm:grid-cols-3 gap-x-8 gap-y-10 mt-12">
        @foreach ($steps as $i => $step)
            {{-- The hairline runs along the top of each column, so the three
                 read as one continuous rule broken by the numerals — the
                 connector in the reference, without drawing a second element. --}}
            <div class="border-t border-line pt-6" data-reveal data-reveal-delay="{{ $i * 110 }}">
                <div class="flex items-baseline gap-3">
                    <span class="font-mono text-xs text-muted tracking-widest shrink-0">
                        [ {{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }} ]
                    </span>
                    <h3 class="text-lg font-medium text-ink tracking-tight">{{ $step['label'] }}</h3>
                </div>

                @if (! empty($step['duration']))
                    <p class="text-xs font-medium text-muted uppercase tracking-wide mt-2">{{ $step['duration'] }}</p>
                @endif

                <p class="text-sm text-muted mt-3 leading-relaxed">{{ $step['body'] }}</p>

                @if (! empty($step['points']))
                    <ul class="flex flex-col gap-2 mt-4">
                        @foreach ($step['points'] as $point)
                            <li class="flex items-start gap-2.5">
                                <span class="w-1 h-1 rounded-full bg-muted shrink-0 mt-2" aria-hidden="true"></span>
                                <span class="text-sm text-muted leading-relaxed">{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
</div>
