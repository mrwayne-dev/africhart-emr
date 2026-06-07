@props([
    'loadingText' => 'Please wait…',
])

{{--
    Submit button with a built-in loading state. Place inside a <form> that has
    x-data="{ loading: false }" @submit="loading = true" — the button reads that
    shared `loading` flag, disables itself, and swaps in a spinner.
--}}
<button type="submit" :disabled="loading"
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-2 transition-colors disabled:opacity-60 disabled:cursor-not-allowed']) }}>
    <span x-show="!loading">{{ $slot }}</span>
    <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
        <x-spinner class="w-4 h-4" />
        {{ $loadingText }}
    </span>
</button>
