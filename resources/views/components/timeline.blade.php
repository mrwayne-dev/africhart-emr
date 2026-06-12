@props([
    'events',
])

<div class="relative">
    @forelse ($events as $event)
        <div class="flex gap-4 pb-6 last:pb-0 relative">
            {{-- Connector line --}}
            @unless ($loop->last)
                <span class="absolute left-[15px] top-8 bottom-0 w-px bg-line" aria-hidden="true"></span>
            @endunless

            {{-- Icon node --}}
            <div class="relative z-10 w-8 h-8 rounded-full bg-warm border border-line flex items-center justify-center shrink-0 text-ink">
                <x-dynamic-component :component="$event['icon']" class="w-4 h-4" />
            </div>

            {{-- Content --}}
            <div class="flex-1 min-w-0 pt-0.5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        @if ($event['link'])
                            <a href="{{ $event['link'] }}" class="text-sm font-medium text-ink hover:underline">{{ $event['title'] }}</a>
                        @else
                            <p class="text-sm font-medium text-ink">{{ $event['title'] }}</p>
                        @endif
                        <p class="text-sm text-muted">{{ $event['subtitle'] }}</p>
                    </div>
                    <time class="text-xs text-muted whitespace-nowrap shrink-0">{{ $event['date']->format('j M Y') }}</time>
                </div>
            </div>
        </div>
    @empty
        <p class="text-sm text-muted">No activity yet.</p>
    @endforelse
</div>
