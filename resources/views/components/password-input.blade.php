@props([
    'name',
    'label',
    'id' => null,
    'autocomplete' => 'current-password',
    'autofocus' => false,
])

@php $id ??= $name; @endphp

<div x-data="{ show: false }">
    <label for="{{ $id }}" class="block text-sm font-medium text-ink-body mb-2">{{ $label }}</label>
    <div class="relative">
        <input
            :type="show ? 'text' : 'password'"
            name="{{ $name }}"
            id="{{ $id }}"
            autocomplete="{{ $autocomplete }}"
            @if ($autofocus) autofocus @endif
            required
            {{ $attributes->merge(['class' => 'w-full bg-warm rounded text-sm text-ink-body px-4 py-3 pr-11 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors']) }}
        >
        <button type="button" @click="show = !show" tabindex="-1"
            class="absolute inset-y-0 right-0 px-3 flex items-center text-muted hover:text-ink"
            :aria-label="show ? 'Hide password' : 'Show password'">
            <x-phosphor-eye x-show="!show" class="w-5 h-5" />
            <x-phosphor-eye-slash x-show="show" class="w-5 h-5" x-cloak />
        </button>
    </div>
    @error($name)
        <p class="mt-2 text-sm text-accent">{{ $message }}</p>
    @enderror
</div>
