@props([
    'items',        // array<int, array{question: string, answer: string}>
    'openFirst' => true,
])

{{--
    Single-open accordion (the Adapt reference): opening one closes the others,
    so the reader never loses their place in a long list. Triggers are real
    <button>s with aria-expanded/aria-controls so keyboard and screen-reader
    users get the same behaviour as a mouse.
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
                    <span class="text-sm font-medium text-ink group-hover:text-ink transition-colors">
                        {{ $item['question'] }}
                    </span>
                    <span class="shrink-0 text-muted group-hover:text-ink transition-colors mt-0.5">
                        <x-phosphor-minus x-show="open === {{ $i }}" x-cloak class="w-4 h-4" />
                        <x-phosphor-plus x-show="open !== {{ $i }}" class="w-4 h-4" />
                    </span>
                </button>
            </h3>

            {{-- x-transition, not x-collapse: the @alpinejs/collapse plugin is not
                 installed and adding a dependency for one accordion isn't worth it.
                 Durations match the app's existing modal transitions. --}}
            <div id="faq-panel-{{ $i }}" x-show="open === {{ $i }}" x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0">
                <p class="text-sm text-muted leading-relaxed pb-5 pr-10">{{ $item['answer'] }}</p>
            </div>
        </div>
    @endforeach
</div>
