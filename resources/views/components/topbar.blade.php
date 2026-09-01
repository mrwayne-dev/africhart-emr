<header class="bg-page border-b border-line px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
        {{-- Hamburger (mobile only) --}}
        <button type="button" @click="sidebarOpen = true" class="md:hidden text-muted hover:text-ink -ml-1">
            <x-phosphor-list class="w-6 h-6" />
        </button>
        <div class="min-w-0">
            {{-- The clinic's own name, above the page name.

                 Persistent context on every screen rather than four edits to
                 four dashboards: staff working across two clinics could not
                 tell from the page which one they were in. The sidebar carries
                 the vendor brand, so the two together answer both questions —
                 whose software, and whose clinic. --}}
            <p class="text-xs text-muted truncate">{{ \App\Support\ClinicIdentity::name() }}</p>
            <h1 class="text-lg font-medium text-ink tracking-tight truncate">@yield('page-title', 'Dashboard')</h1>
            <p class="text-sm text-muted truncate">@yield('page-subtitle', '')</p>
        </div>
    </div>

    <div class="flex items-center gap-5">
        {{-- The CLINIC's date, not the server's. now() is UTC; a clinic in
             Lagos reading this between 00:00 and 01:00 local was shown
             yesterday's date on its own screen. --}}
        <span class="text-sm text-muted hidden sm:inline">{{ \App\Support\ClinicIdentity::today()->format('l, j F Y') }}</span>

        <button type="button"
            x-data
            @click="$dispatch('open-modal', 'logout')"
            class="flex items-center gap-2 text-sm text-muted hover:text-ink transition-colors">
            <x-phosphor-sign-out class="w-4 h-4" />
            Logout
        </button>
    </div>
</header>

{{-- Logout confirmation modal --}}
<x-modal name="logout" title="Log out?" maxWidth="max-w-sm">
    <p class="text-sm text-muted">Are you sure you want to log out of AfriChart EMR?</p>

    <form method="POST" action="{{ route('logout') }}" class="flex items-center justify-end gap-3 mt-6"
        x-data="{ loading: false }" @submit="loading = true">
        @csrf
        <button type="button" @click="$dispatch('close-modal', 'logout')"
            class="text-sm text-muted hover:text-ink transition-colors px-2">
            Cancel
        </button>
        <x-submit-button loadingText="Logging out…"
            class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90">
            Log out
        </x-submit-button>
    </form>
</x-modal>
