@props([
    'title',
    'value',
    'icon' => 'phosphor-chart-bar',
])

<div class="bg-page border border-line rounded-card p-6 flex flex-col justify-between min-h-[140px]">
    <div class="flex items-start justify-between">
        <p class="text-sm text-muted">{{ $title }}</p>
        <x-dynamic-component :component="$icon" class="w-6 h-6 text-ink" />
    </div>
    <p class="text-5xl font-medium text-ink tracking-tight mt-6">{{ $value }}</p>
</div>
