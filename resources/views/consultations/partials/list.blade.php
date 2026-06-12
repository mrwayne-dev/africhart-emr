{{-- Live-swappable consultation list. Rendered on the page and by GET /consultations/live. --}}
<x-consultation-table :consultations="$consultations" />
@if ($consultations->hasPages())
    <div class="mt-6">{{ $consultations->links() }}</div>
@endif
