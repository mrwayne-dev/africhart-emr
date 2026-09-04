@extends('layouts.app')

@section('title', 'Set up your clinic')
@section('page-title', 'Welcome')
@section('page-subtitle', 'Your team')

@section('content')
<x-setup-shell :step="$step" :progress="$progress"
    title="Bring your team in"
    lead="You already have an administrator account — that is the one you are using. Invite the people who will work alongside you.">

    {{-- The invite link panel, same as Team & Seats: it is flashed once and the
         email may be stubbed, so the admin always has a path that works. --}}
    @if (session('invite_link'))
        <div class="border rounded-card p-5 mb-6 {{ session('invite_delivered') ? 'border-line bg-warm/50' : 'border-accent/40 bg-accent/5' }}"
            x-data="{
                copied: false,
                async copy() {
                    try { await navigator.clipboard.writeText(@js(session('invite_link'))); }
                    catch (e) { this.$refs.link.select(); document.execCommand('copy'); }
                    this.copied = true; setTimeout(() => (this.copied = false), 2000);
                },
            }">
            <p class="text-sm font-medium text-ink">
                @if (session('invite_delivered'))
                    Invitation link for {{ session('invite_email') }}
                @else
                    The email could not be sent — send this link instead
                @endif
            </p>
            <div class="flex flex-col sm:flex-row gap-2 mt-3">
                <input type="text" x-ref="link" readonly value="{{ session('invite_link') }}"
                    @focus="$event.target.select()"
                    class="flex-1 min-w-0 bg-page rounded text-xs font-mono text-ink-body px-3 py-2.5
                        border border-line focus:border-ink focus:outline-none">
                <button type="button" @click="copy()"
                    class="shrink-0 inline-flex items-center justify-center gap-1.5 bg-ink text-white
                        rounded-full px-4 py-2.5 text-sm font-medium hover:bg-ink/90 transition-colors">
                    <span x-text="copied ? 'Copied' : 'Copy link'">Copy link</span>
                </button>
            </div>
            <p class="text-xs text-muted mt-3">Shown once — it is stored hashed and cannot be retrieved later.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('staff.invitations.store') }}"
        class="grid sm:grid-cols-3 gap-5 items-start" x-data="{ loading: false }" @submit="loading = true">
        @csrf

        <x-form-field name="email" label="Email" type="email" required
            placeholder="name@clinic.com" autocomplete="off" />

        <x-form-field name="name" label="Name" optional placeholder="Dr. Emeka Okafor" />

        <x-form-field name="role" label="Role" required
            :options="collect($roles)->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all()" />

        <div class="sm:col-span-3">
            <x-submit-button loadingText="Creating…"
                class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90">
                Send invitation
            </x-submit-button>
        </div>
    </form>

    <div class="mt-8 pt-6 border-t border-line">
        <form method="POST" action="{{ route('setup.complete') }}">
            @csrf
            <x-submit-button loadingText="Finishing…"
                class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90">
                Finish setup
            </x-submit-button>
            <p class="text-xs text-muted mt-2">
                You can invite more people any time from Settings → Team &amp; Seats.
            </p>
        </form>
    </div>
</x-setup-shell>
@endsection
