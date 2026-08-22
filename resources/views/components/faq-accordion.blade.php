@props([
    'items',        // array<int, array{question: string, answer: string}>
    'openFirst' => true,
])

{{--
    Single-open accordion. Opening one closes the others so the reader never
    loses their place in a long list.

    Height is animated with the grid-template-rows 0fr -> 1fr technique: the
    panel is a grid whose single row grows from zero, with overflow hidden on
    the child. That genuinely animates height in pure CSS — no @alpinejs/collapse
    dependency and no JS measuring scrollHeight. The previous version only faded
    and translated, so the panel popped the layout open.

    The control animates too: one icon rotated 45deg rather than swapping a plus
    for a minus.
--}}
<div x-data="{ open: {{ $openFirst ? 0 : 'null' }} }" class="divide-y divide-line border-t border-b border-line">
    @foreach ($items as $i => $item)
        <div>
            <h3>
                <button type="button"
                    @click="open = (open === {{ $i }} ? null : {{ $i }})"
                    :aria-expanded="open === {{ $i }} ? 'true' : 'false'"
                    aria-controls="faq-panel-{{ $i }}"
                    class="w-full flex items-start justify-between gap-6 text-left py-5 group">
                    <span class="text-sm sm:text-base font-medium text-ink transition-colors">
                        {{ $item['question'] }}
                    </span>
                    <span class="shrink-0 text-muted group-hover:text-ink transition-colors mt-0.5">
                        <x-phosphor-plus class="w-4 h-4 transition-transform duration-500 ease-out"
                            ::class="open === {{ $i }} && 'rotate-45'" />
                    </span>
                </button>
            </h3>

            <div id="faq-panel-{{ $i }}"
                class="grid transition-[grid-template-rows] duration-500 ease-out"
                :class="open === {{ $i }} ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                <div class="overflow-hidden">
                    <p class="text-sm text-muted leading-relaxed pb-5 pr-10">{{ $item['answer'] }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>
