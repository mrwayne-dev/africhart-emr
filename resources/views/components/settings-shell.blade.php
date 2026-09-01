@props(['active'])

{{--
    The settings hub shell.

    Every settings screen renders inside this: a left-hand section list, the
    screen itself on the right. Sections are declared once, here, so a new one
    cannot be added to the nav and forgotten in the routing (or the reverse).

    Sections whose route does not exist yet are listed as `null` and render
    disabled with a reason. That is deliberate rather than hiding them: an owner
    who has been told the product has a Billing section should see it and learn
    it is not ready, not be left wondering where it went. It also makes the B1
    dependency visible in the interface rather than only in a checklist.
--}}
@php
    $sections = [
        [
            'key' => 'profile',
            'label' => 'Clinic Profile',
            'icon' => 'phosphor-buildings',
            'blurb' => 'Name, contact details, fee, timezone',
            'route' => 'settings.profile.edit',
        ],
        [
            'key' => 'team',
            'label' => 'Team & Seats',
            'icon' => 'phosphor-users-three',
            'blurb' => 'Invite staff and manage access',
            'route' => Route::has('settings.team.index') ? 'settings.team.index' : null,
        ],
        [
            'key' => 'catalogue',
            'label' => 'Drug Catalogue',
            'icon' => 'phosphor-pill',
            'blurb' => 'Your drug list, at your prices',
            'route' => Route::has('settings.catalogue.index') ? 'settings.catalogue.index' : null,
        ],
        [
            'key' => 'branding',
            'label' => 'Branding',
            'icon' => 'phosphor-image-square',
            'blurb' => 'Your logo on your documents',
            'route' => Route::has('settings.branding.edit') ? 'settings.branding.edit' : null,
        ],
        [
            'key' => 'billing',
            'label' => 'Billing & Plan',
            'icon' => 'phosphor-credit-card',
            'blurb' => 'Subscription, invoices and plan',
            'route' => null,
            'unavailable' => 'Available once subscription billing is live.',
        ],
    ];
@endphp

<div class="grid lg:grid-cols-12 gap-6 lg:gap-8">

    {{-- Section list. A <nav> with aria-current so the active section is
         announced, not merely coloured. --}}
    <nav class="lg:col-span-3" aria-label="Settings sections">
        <ul class="flex lg:flex-col gap-1 overflow-x-auto lg:overflow-visible pb-1 lg:pb-0">
            @foreach ($sections as $section)
                @php
                    $isActive = $active === $section['key'];
                    $isAvailable = $section['route'] !== null;
                @endphp

                <li class="shrink-0 lg:shrink">
                    @if ($isAvailable)
                        <a href="{{ route($section['route']) }}"
                            @if ($isActive) aria-current="page" @endif
                            class="flex items-start gap-3 px-3 py-2.5 rounded-card text-sm transition-colors
                                {{ $isActive ? 'bg-warm text-ink' : 'text-muted hover:bg-warm hover:text-ink' }}">
                            <x-dynamic-component :component="$section['icon']" class="w-5 h-5 shrink-0 mt-px" aria-hidden="true" />
                            <span class="min-w-0">
                                <span class="block font-medium whitespace-nowrap">{{ $section['label'] }}</span>
                                <span class="hidden lg:block text-xs text-muted mt-0.5">{{ $section['blurb'] }}</span>
                            </span>
                        </a>
                    @else
                        <span class="flex items-start gap-3 px-3 py-2.5 rounded-card text-sm text-muted/60 cursor-not-allowed"
                            title="{{ $section['unavailable'] ?? 'Not available yet.' }}">
                            <x-dynamic-component :component="$section['icon']" class="w-5 h-5 shrink-0 mt-px" aria-hidden="true" />
                            <span class="min-w-0">
                                <span class="block font-medium whitespace-nowrap">{{ $section['label'] }}</span>
                                <span class="hidden lg:block text-xs mt-0.5">{{ $section['unavailable'] ?? 'Coming soon' }}</span>
                            </span>
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="lg:col-span-9 min-w-0">
        {{ $slot }}
    </div>
</div>
