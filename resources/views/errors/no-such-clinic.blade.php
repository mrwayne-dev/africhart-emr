@extends('layouts.guest')

@section('title', 'No such clinic — AfriChart')

@section('content')
    {{--
        Shown when a subdomain does not match any clinic in the registry.

        Deliberately not a 444 or a bare 404: the person seeing this is almost
        always a clinic's own staff member who mistyped their address, and the
        useful thing is to help them find it rather than to close the connection.
        Junk hostnames pointed at the box still get 444 at the nginx layer and
        never reach this page.

        It says nothing about which clinics DO exist — a form that confirms or
        denies a name would enumerate our customer list for anyone who asked.
    --}}
    <div class="bg-page border border-line rounded-card p-8 text-center">
        <div class="w-12 h-12 bg-warm rounded-full flex items-center justify-center mx-auto mb-5">
            <x-phosphor-magnifying-glass class="w-6 h-6 text-ink" aria-hidden="true" />
        </div>

        <h1 class="text-xl font-medium text-ink tracking-tight">No clinic at this address</h1>

        @if ($subdomain)
            <p class="text-sm text-muted mt-3 leading-relaxed">
                We could not find a clinic at
                <span class="font-mono text-ink-body">{{ $subdomain }}.{{ $rootDomain }}</span>.
                Check the spelling, or ask your clinic administrator for the right link.
            </p>
        @else
            <p class="text-sm text-muted mt-3 leading-relaxed">
                This address does not belong to a clinic. If you are staff, ask your
                clinic administrator for your sign-in link.
            </p>
        @endif

        <div class="border-t border-line mt-7 pt-6 flex flex-col gap-3">
            <a href="https://{{ $rootDomain }}"
                class="bg-ink text-white rounded-full px-5 py-3 text-sm font-medium hover:bg-ink/90 transition-colors">
                Go to AfriChart
            </a>
            <a href="https://{{ $rootDomain }}/contact"
                class="text-sm text-muted hover:text-ink transition-colors">
                Contact us for help
            </a>
        </div>
    </div>
@endsection
