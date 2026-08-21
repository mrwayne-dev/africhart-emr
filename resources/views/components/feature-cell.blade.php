@props([
    'title',
    'icon' => 'phosphor-check',
])

{{--
    Cell for the bordered feature grid. The grid draws hairlines between cells
    rather than gapping them apart (the customer.io "journey" pattern), so the
    cell itself carries no border — the parent grid supplies them.
--}}
<div class="p-6 sm:p-8">
    <x-dynamic-component :component="$icon" class="w-6 h-6 text-ink mb-6" />
    <h3 class="text-base font-medium text-ink tracking-tight">{{ $title }}</h3>
    <p class="text-sm text-muted mt-2 leading-relaxed">{{ $slot }}</p>
</div>
