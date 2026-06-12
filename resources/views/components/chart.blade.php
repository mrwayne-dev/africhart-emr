@props([
    'id',
    'type' => 'line',
    'labels' => [],
    'data' => [],
    'label' => '',
    'colors' => null, // optional array for pie/donut/bar segments
])

<div class="bg-page border border-line rounded-card p-6">
    @if ($label)
        <h3 class="text-sm font-medium text-ink mb-4">{{ $label }}</h3>
    @endif
    {{-- Fixed-height, relatively-positioned wrapper: required by Chart.js when
         maintainAspectRatio is false, otherwise the canvas chases its own height
         and the chart grows indefinitely. --}}
    <div class="relative h-55">
        <canvas id="{{ $id }}"></canvas>
    </div>
</div>

@once
    @push('scripts')
        <script>
            window.AfriChartPalette = ['#1a1a1a', '#c2001d', '#636363', '#9ca3af', '#d1d5db'];
        </script>
    @endpush
@endonce

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById(@json($id));
            if (!el || !window.Chart) return;

            const type = @json($type);
            const segmented = ['pie', 'doughnut', 'bar'].includes(type);
            const palette = @json($colors) ?? window.AfriChartPalette;

            new window.Chart(el, {
                type,
                data: {
                    labels: @json($labels),
                    datasets: [{
                        label: @json($label),
                        data: @json(array_values((array) $data)),
                        borderColor: segmented ? '#ffffff' : '#1a1a1a',
                        backgroundColor: segmented ? palette : 'rgba(26, 26, 26, 0.08)',
                        borderWidth: segmented ? 2 : 2,
                        tension: 0.3,
                        fill: type === 'line',
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: type === 'doughnut' || type === 'pie', position: 'bottom' } },
                    scales: segmented && type !== 'bar' ? {} : {
                        y: { beginAtZero: true, grid: { color: '#ececec' }, ticks: { precision: 0 } },
                        x: { grid: { display: false } },
                    },
                },
            });
        });
    </script>
@endpush
