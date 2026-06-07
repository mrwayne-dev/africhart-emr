@props([
    'label',
    'value',
])

<div class="flex flex-col sm:flex-row sm:items-center px-6 py-4">
    <dt class="w-full sm:w-56 text-sm text-muted shrink-0">{{ $label }}</dt>
    <dd class="text-sm text-ink mt-1 sm:mt-0">{{ $value }}</dd>
</div>
