@php
    $user = auth()->user();
@endphp

{{-- Mobile backdrop --}}
<div x-show="sidebarOpen" x-cloak
    x-transition:enter="transition-opacity ease-out duration-200"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-in duration-150"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    @click="sidebarOpen = false"
    class="fixed inset-0 bg-ink/40 z-30 md:hidden"></div>

<aside
    class="fixed md:sticky top-0 inset-y-0 left-0 z-40 w-64 bg-page border-r border-line h-screen flex flex-col shrink-0
        transition-transform duration-200 -translate-x-full md:translate-x-0"
    :class="{ 'translate-x-0': sidebarOpen }">

    {{-- Logo --}}
    <div class="px-6 py-5 border-b border-line flex items-center justify-between">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <img src="{{ asset('images/africhart-logo.svg') }}" alt="AfriChart" class="w-9 h-9">
            <span class="text-lg font-medium text-ink tracking-tight">AfriChart</span>
        </a>
        {{-- Close (mobile only) --}}
        <button type="button" @click="sidebarOpen = false" class="md:hidden text-muted hover:text-ink">
            <x-phosphor-x class="w-5 h-5" />
        </button>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 px-4 py-4 space-y-1">
        <a href="{{ route('dashboard') }}" @click="sidebarOpen = false"
            class="flex items-center gap-3 px-3 py-2.5 rounded-card text-sm font-medium transition-colors
                {{ request()->routeIs('dashboard') ? 'bg-warm text-ink' : 'text-muted hover:bg-warm hover:text-ink' }}">
            <x-phosphor-squares-four class="w-5 h-5" />
            Dashboard
        </a>

        <a href="{{ route('patients.index') }}" @click="sidebarOpen = false"
            class="flex items-center gap-3 px-3 py-2.5 rounded-card text-sm font-medium transition-colors
                {{ request()->routeIs('patients.*') ? 'bg-warm text-ink' : 'text-muted hover:bg-warm hover:text-ink' }}">
            <x-phosphor-users class="w-5 h-5" />
            Patients
        </a>

        <a href="{{ route('queue.index') }}" @click="sidebarOpen = false"
            class="flex items-center gap-3 px-3 py-2.5 rounded-card text-sm font-medium transition-colors
                {{ request()->routeIs('queue.*') ? 'bg-warm text-ink' : 'text-muted hover:bg-warm hover:text-ink' }}">
            <x-phosphor-list-checks class="w-5 h-5" />
            Queue
        </a>

        @unless ($user->isReceptionist())
            <a href="{{ route('consultations.index') }}" @click="sidebarOpen = false"
                class="flex items-center gap-3 px-3 py-2.5 rounded-card text-sm font-medium transition-colors
                    {{ request()->routeIs('consultations.*') ? 'bg-warm text-ink' : 'text-muted hover:bg-warm hover:text-ink' }}">
                <x-phosphor-stethoscope class="w-5 h-5" />
                Consultations
            </a>
        @endunless

        @unless ($user->isNurse())
            <a href="{{ route('invoices.index') }}" @click="sidebarOpen = false"
                class="flex items-center gap-3 px-3 py-2.5 rounded-card text-sm font-medium transition-colors
                    {{ request()->routeIs('invoices.*') ? 'bg-warm text-ink' : 'text-muted hover:bg-warm hover:text-ink' }}">
                <x-phosphor-receipt class="w-5 h-5" />
                Invoices
            </a>
        @endunless

        @if ($user->isAdmin())
            <a href="{{ route('staff.index') }}" @click="sidebarOpen = false"
                class="flex items-center gap-3 px-3 py-2.5 rounded-card text-sm font-medium transition-colors
                    {{ request()->routeIs('staff.*') ? 'bg-warm text-ink' : 'text-muted hover:bg-warm hover:text-ink' }}">
                <x-phosphor-users-three class="w-5 h-5" />
                Staff
            </a>

            <a href="{{ route('medications.index') }}" @click="sidebarOpen = false"
                class="flex items-center gap-3 px-3 py-2.5 rounded-card text-sm font-medium transition-colors
                    {{ request()->routeIs('medications.*') ? 'bg-warm text-ink' : 'text-muted hover:bg-warm hover:text-ink' }}">
                <x-phosphor-pill class="w-5 h-5" />
                Drug Catalog
            </a>

            <a href="{{ route('audit.index') }}" @click="sidebarOpen = false"
                class="flex items-center gap-3 px-3 py-2.5 rounded-card text-sm font-medium transition-colors
                    {{ request()->routeIs('audit.*') ? 'bg-warm text-ink' : 'text-muted hover:bg-warm hover:text-ink' }}">
                <x-phosphor-clock-counter-clockwise class="w-5 h-5" />
                Audit Log
            </a>
        @endif
    </nav>

    {{-- User info --}}
    <div class="px-4 py-4 border-t border-line">
        <div class="flex items-center gap-3 px-3">
            <div class="w-9 h-9 bg-warm rounded-full flex items-center justify-center text-ink text-xs font-medium shrink-0">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-ink truncate">{{ $user->name }}</p>
                <p class="text-xs text-muted">{{ $user->role->label() }}</p>
            </div>
        </div>
    </div>
</aside>
