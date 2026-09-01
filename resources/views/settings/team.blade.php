@extends('layouts.app')

@section('title', 'Team & Seats — Settings')
@section('page-title', 'Settings')
@section('page-subtitle', 'Team & Seats')

@section('content')
<x-settings-shell active="team">

    {{--
        ── The invitation link, shown once ────────────────────────────────────

        This panel is the point of the screen.

        The link is flashed for exactly one redirect and stored nowhere: the
        invitations table keeps a SHA-256 hash so a leaked database is not a set
        of working invites. That guarantee used to have a cost — the link went
        only to the email, so if mail failed or was stubbed it was gone for
        good, and the admin could only revoke and hope the next send worked.

        Now the email is a convenience and this is the reliable path.
    --}}
    @if (session('invite_link'))
        <div class="border rounded-card p-5 mb-6 {{ session('invite_delivered') ? 'border-line bg-warm/50' : 'border-accent/40 bg-accent/5' }}"
            x-data="{
                copied: false,
                async copy() {
                    try {
                        await navigator.clipboard.writeText(@js(session('invite_link')));
                    } catch (e) {
                        /* Clipboard API needs a secure context; select the text
                           instead so the admin can copy it by hand rather than
                           being told nothing happened. */
                        this.$refs.link.select();
                        document.execCommand('copy');
                    }
                    this.copied = true;
                    setTimeout(() => (this.copied = false), 2000);
                },
            }">

            <div class="flex items-start gap-3">
                <x-dynamic-component :component="session('invite_delivered') ? 'phosphor-link' : 'phosphor-warning'"
                    class="w-5 h-5 shrink-0 mt-0.5 {{ session('invite_delivered') ? 'text-ink' : 'text-accent' }}"
                    aria-hidden="true" />

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-ink">
                        @if (session('invite_delivered'))
                            Invitation link for {{ session('invite_email') }}
                        @else
                            The email could not be sent — send this link instead
                        @endif
                    </p>

                    <p class="text-sm text-muted mt-1 leading-relaxed">
                        @if (session('invite_delivered'))
                            We emailed this to them. Copy it if you would rather send it yourself —
                            over WhatsApp, or in person.
                        @else
                            The invitation itself is fine. Only the email failed.
                        @endif
                        <span class="text-ink-body">It works once, and expires in
                        {{ \App\Models\StaffInvitation::EXPIRES_AFTER_DAYS }} days.</span>
                    </p>

                    <div class="flex flex-col sm:flex-row gap-2 mt-3">
                        {{-- readonly, not disabled: a disabled input cannot be
                             selected, which is the fallback when the clipboard
                             API is unavailable. --}}
                        <input type="text" x-ref="link" readonly value="{{ session('invite_link') }}"
                            @focus="$event.target.select()"
                            class="flex-1 min-w-0 bg-page rounded text-xs font-mono text-ink-body px-3 py-2.5
                                border border-line focus:border-ink focus:outline-none">

                        <button type="button" @click="copy()"
                            class="shrink-0 inline-flex items-center justify-center gap-1.5 bg-ink text-white
                                rounded-full px-4 py-2.5 text-sm font-medium hover:bg-ink/90 transition-colors">
                            <x-phosphor-copy class="w-4 h-4" x-show="! copied" aria-hidden="true" />
                            <x-phosphor-check class="w-4 h-4" x-show="copied" x-cloak aria-hidden="true" />
                            <span x-text="copied ? 'Copied' : 'Copy link'">Copy link</span>
                        </button>
                    </div>

                    <p class="text-xs text-muted mt-3">
                        This is the only time the link is shown — it is stored hashed, so nobody,
                        including us, can retrieve it later. If you lose it, revoke the invitation
                        below and send a new one.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Invite someone ──────────────────────────────────────────────── --}}
    <div class="bg-page border border-line rounded-card p-5 sm:p-6 mb-6"
        x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }">

        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h3 class="text-sm font-medium text-ink">Invite a team member</h3>
                <p class="text-sm text-muted mt-1">
                    They set their own password from the link. It works once and expires in
                    {{ \App\Models\StaffInvitation::EXPIRES_AFTER_DAYS }} days, and the role you
                    choose here is the role they get — they cannot change it.
                </p>
            </div>

            <button type="button" @click="open = ! open"
                class="shrink-0 inline-flex items-center gap-1.5 bg-ink text-white rounded-full px-4 py-2.5 text-sm font-medium hover:bg-ink/90 transition-colors">
                <x-phosphor-paper-plane-tilt class="w-4 h-4" aria-hidden="true" />
                <span x-text="open ? 'Cancel' : 'Invite'">Invite</span>
            </button>
        </div>

        <form x-show="open" x-cloak method="POST" action="{{ route('staff.invitations.store') }}"
            class="mt-6 pt-6 border-t border-line grid sm:grid-cols-3 gap-5 items-start">
            @csrf

            <x-form-field name="email" label="Email" type="email" required
                placeholder="name@clinic.com" autocomplete="off" />

            <x-form-field name="name" label="Name" optional placeholder="Dr. Emeka Okafor" />

            <x-form-field name="role" label="Role" required
                :options="collect($roles)->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all()" />

            <div class="sm:col-span-3">
                <x-submit-button loadingText="Creating…"
                    class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90">
                    Create invitation
                </x-submit-button>
            </div>
        </form>
    </div>

{{-- ── Pending invitations ─────────────────────────────────────────────── --}}
@if ($invitations->isNotEmpty())
    <div class="bg-page border border-line rounded-card mb-6">
        <div class="px-4 py-3 border-b border-line flex items-center gap-2">
            <x-phosphor-clock-countdown class="w-4 h-4 text-muted" aria-hidden="true" />
            <h2 class="text-sm font-medium text-ink">Awaiting acceptance ({{ $invitations->count() }})</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-muted border-b border-line whitespace-nowrap">
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Role</th>
                        <th class="px-4 py-3 font-medium">Invited by</th>
                        <th class="px-4 py-3 font-medium">Expires</th>
                        <th class="px-4 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invitations as $invitation)
                        <tr class="border-b border-line last:border-0 even:bg-warm/60">
                            <td class="px-4 py-3 font-medium text-ink">{{ $invitation->email }}</td>
                            <td class="px-4 py-3 text-muted">{{ $invitation->role->label() }}</td>
                            <td class="px-4 py-3 text-muted">{{ $invitation->inviter?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-muted">{{ $invitation->expires_at->diffForHumans(['parts' => 1]) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <form method="POST" action="{{ route('staff.invitations.destroy', $invitation) }}"
                                    onsubmit="return confirm('Revoke the invitation for {{ $invitation->email }}? The link will stop working immediately.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 text-muted hover:text-accent transition-colors">
                                        <x-phosphor-trash class="w-4 h-4" />
                                        Revoke
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="bg-page border border-line rounded-card">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-muted border-b border-line whitespace-nowrap">
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">Email</th>
                    <th class="px-4 py-3 font-medium">Role</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($staff as $member)
                    @php $isActive = is_null($member->deleted_at); @endphp
                    <tr class="border-b border-line last:border-0 even:bg-warm/60">
                        <td class="px-4 py-3 font-medium text-ink">
                            {{ $member->name }}
                            @if ($member->id === auth()->id())
                                <span class="text-xs text-muted font-normal">(you)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $member->email }}</td>
                        <td class="px-4 py-3 text-muted">{{ $member->role->label() }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                {{ $isActive ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $isActive ? 'Active' : 'Deactivated' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if ($member->id === auth()->id())
                                <span class="text-muted text-xs">—</span>
                            @elseif ($isActive)
                                <form method="POST" action="{{ route('staff.deactivate', $member) }}"
                                    onsubmit="return confirm('Deactivate {{ $member->name }}? They will no longer be able to sign in.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 text-muted hover:text-accent transition-colors">
                                        <x-phosphor-prohibit class="w-4 h-4" />
                                        Deactivate
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('staff.reactivate', $member) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex items-center gap-1 text-muted hover:text-ink transition-colors">
                                        <x-phosphor-arrow-counter-clockwise class="w-4 h-4" />
                                        Reactivate
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-muted">No staff found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-settings-shell>
@endsection
