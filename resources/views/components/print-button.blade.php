<button type="button" onclick="window.print()"
    {{ $attributes->merge(['class' => 'no-print inline-flex items-center gap-1.5 border border-line text-ink rounded-full px-4 py-2 text-sm font-medium hover:bg-warm transition-colors']) }}>
    <x-phosphor-printer class="w-4 h-4" />
    Print
</button>
