{{-- Toast stack. Driven by the Alpine $store.toasts. --}}
<div
    x-data
    x-init="
        @if (session('success')) window.toast('success', @js(session('success'))); @endif
        @if (session('error')) window.toast('error', @js(session('error'))); @endif
    "
    class="fixed top-4 right-4 z-[100] flex flex-col gap-2 w-80 max-w-[calc(100vw-2rem)]"
    aria-live="polite"
>
    <template x-for="t in $store.toasts.items" :key="t.id">
        <div
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-8 scale-95"
            x-transition:enter-end="opacity-100 translate-x-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="flex items-start gap-3 rounded-card border border-l-4 px-4 py-3.5 bg-page shadow-xl shadow-ink/15 ring-1 ring-ink/5"
            :class="t.type === 'error' ? 'border-l-accent border-accent/20' : 'border-l-ink border-line'"
        >
            <span class="mt-0.5 shrink-0" :class="t.type === 'error' ? 'text-accent' : 'text-ink'">
                <svg x-show="t.type !== 'error'" class="w-6 h-6" viewBox="0 0 256 256" fill="currentColor"><path d="M173.66,98.34a8,8,0,0,1,0,11.32l-56,56a8,8,0,0,1-11.32,0l-24-24a8,8,0,0,1,11.32-11.32L112,148.69l50.34-50.35A8,8,0,0,1,173.66,98.34ZM232,128A104,104,0,1,1,128,24,104.11,104.11,0,0,1,232,128Zm-16,0a88,88,0,1,0-88,88A88.1,88.1,0,0,0,216,128Z"/></svg>
                <svg x-show="t.type === 'error'" class="w-6 h-6" viewBox="0 0 256 256" fill="currentColor"><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm37.66,130.34a8,8,0,0,1-11.32,11.32L128,139.31l-26.34,26.35a8,8,0,0,1-11.32-11.32L116.69,128,90.34,101.66a8,8,0,0,1,11.32-11.32L128,116.69l26.34-26.35a8,8,0,0,1,11.32,11.32L139.31,128Z"/></svg>
            </span>
            <p class="flex-1 text-sm font-medium text-ink-body leading-snug" x-text="t.message"></p>
            <button type="button" @click="$store.toasts.remove(t.id)" class="text-muted hover:text-ink shrink-0">
                <x-phosphor-x class="w-4 h-4" />
            </button>
        </div>
    </template>
</div>
