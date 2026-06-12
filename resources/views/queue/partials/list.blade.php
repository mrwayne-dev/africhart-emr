{{-- Live-swappable queue list. Rendered both on the page and by GET /queue/live. --}}
<x-queue-table :queue="$queue" :doctors="$doctors" />
