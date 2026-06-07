@props([
    'name',
    'title' => null,
    'maxWidth' => 'max-w-lg',
])

{{--
    Reusable modal. Open from anywhere with:
        $dispatch('open-modal', 'the-name')
    Close with $dispatch('close-modal', 'the-name') or the built-in controls.
--}}
<div
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') { open = true }"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') { open = false }"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[90] flex items-center justify-center p-4"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-ink/40"
        @click="open = false"
    ></div>

    {{-- Panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative bg-page rounded-card border border-line w-full {{ $maxWidth }} max-h-[90vh] overflow-y-auto"
    >
        @if ($title)
            <div class="flex items-center justify-between px-6 py-4 border-b border-line sticky top-0 bg-page">
                <h2 class="text-base font-medium text-ink tracking-tight">{{ $title }}</h2>
                <button type="button" @click="open = false" class="text-muted hover:text-ink">
                    <x-phosphor-x class="w-5 h-5" />
                </button>
            </div>
        @endif

        <div class="p-6">
            {{ $slot }}
        </div>
    </div>
</div>
