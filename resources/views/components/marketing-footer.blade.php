{{--
    Multi-column footer, structure from the customer.io reference.

    The reference is dark; ours is warm off-white with a hairline top border —
    the site stays entirely light so marketing and the EMR feel continuous.
--}}
<footer class="bg-warm border-t border-line">
    <div class="max-w-7xl mx-auto px-6 sm:px-8 py-14 sm:py-16">

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-8 sm:gap-5">

            {{-- Brand column spans full width on mobile so the links pair up neatly below it. --}}
            <div class="col-span-2 sm:col-span-1">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 rounded-card">
                    <img src="{{ asset('images/africhart-logo.svg') }}" alt="" class="w-8 h-8">
                    <span class="text-lg font-medium text-ink tracking-tight">AfriChart</span>
                </a>
                <p class="text-sm text-muted mt-4 max-w-[22rem]">
                    Clinic management software built in Port Harcourt, for Nigerian private clinics.
                </p>

                {{-- The same address published on the three legal pages as the
                     contact for exercising data rights. Keep them identical. --}}
                <a href="mailto:hello@africhartemr.com"
                    class="inline-flex items-center gap-2 text-sm text-muted hover:text-ink transition-colors mt-4 rounded">
                    <x-phosphor-envelope-simple class="w-4 h-4 shrink-0" aria-hidden="true" />
                    hello@africhartemr.com
                </a>
            </div>

            @foreach ([
                'Product' => [
                    ['Features', 'features'],
                    ['Pricing', 'pricing'],
                    ['Book a demo', 'demo'],
                    ['Get started', 'signup'],
                ],
                'Company' => [
                    ['About', 'about'],
                    ['Sign in', 'login'],
                ],
                'Legal' => [
                    ['Privacy', 'legal.privacy'],
                    ['Terms', 'legal.terms'],
                    ['Data processing', 'legal.dpa'],
                ],
            ] as $heading => $links)
                <div>
                    <h2 class="text-xs font-semibold text-muted uppercase tracking-wide mb-4">{{ $heading }}</h2>
                    <ul class="flex flex-col gap-2.5">
                        @foreach ($links as [$label, $route])
                            <li>
                                <a href="{{ route($route) }}"
                                    class="text-sm text-muted hover:text-ink transition-colors">
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        {{-- Bottom bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mt-12 pt-6 border-t border-line">
            <p class="text-xs text-muted">
                &copy; {{ now()->year }} AfriChart Technologies. All rights reserved.
            </p>
            <p class="text-xs text-muted">
                Built by <a href="https://mgbah.dev" rel="noopener"
                    class="text-ink hover:underline">Lymora Labs</a>
            </p>
        </div>
    </div>
</footer>
