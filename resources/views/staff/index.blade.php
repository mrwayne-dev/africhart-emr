@extends('layouts.app')

@section('title', 'Staff — AfriChart EMR')
@section('page-title', 'Staff')
@section('page-subtitle', 'Team members with access to this clinic')

@section('content')

{{-- ── Invite someone ────────────────────────────────────────────────────
     Placed above the table because on this page it is the primary action:
     the only way an account is created at this clinic. --}}
<div class="bg-page border border-line rounded-card p-6 mb-6"
    x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }">

    <div class="flex items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-medium text-ink">Invite a team member</h2>
            <p class="text-sm text-muted mt-1">
                They get an email with a link to set their own password. The link
                works once and expires in {{ \App\Models\StaffInvitation::EXPIRES_AFTER_DAYS }} days.
            </p>
        </div>

        <button type="button" @click="open = ! open"
            class="shrink-0 inline-flex items-center gap-1.5 bg-ink text-white rounded-full px-4 py-2.5 text-sm font-medium hover:bg-ink/90 transition-colors">
            <x-phosphor-paper-plane-tilt class="w-4 h-4" aria-hidden="true" />
            <span x-text="open ? 'Cancel' : 'Send invitation'">Send invitation</span>
        </button>
    </div>

    <form x-show="open" x-cloak method="POST" action="{{ route('staff.invitations.store') }}"
        class="mt-6 pt-6 border-t border-line grid sm:grid-cols-3 gap-4 items-start"
        x-data="{ loading: false }" @submit="loading = true">
        @csrf

        <div class="sm:col-span-1">
            <label for="invite_email" class="block text-sm font-medium text-ink-body mb-2">Email</label>
            <input type="email" name="email" id="invite_email" value="{{ old('email') }}" required
                placeholder="name@clinic.com"
                class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                    focus:bg-page focus:border-ink focus:outline-none transition-colors">
            @error('email')
                <p class="mt-2 text-sm text-accent">{{ $message }}</p>
            @enderror
        </div>

        <div class="sm:col-span-1">
            <label for="invite_name" class="block text-sm font-medium text-ink-body mb-2">
                Name <span class="text-muted font-normal">(optional)</span>
            </label>
            <input type="text" name="name" id="invite_name" value="{{ old('name') }}"
                placeholder="Dr. Emeka Okafor"
                class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                    focus:bg-page focus:border-ink focus:outline-none transition-colors">
            @error('name')
                <p class="mt-2 text-sm text-accent">{{ $message }}</p>
            @enderror
        </div>

        <div class="sm:col-span-1">
            {{-- The role is fixed HERE, by an admin, and travels on the
                 invitation. The person accepting never chooses it. --}}
            <label for="invite_role" class="block text-sm font-medium text-ink-body mb-2">Role</label>
            <select name="role" id="invite_role" required
                class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                    focus:bg-page focus:border-ink focus:outline-none transition-colors">
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </select>
            @error('role')
                <p class="mt-2 text-sm text-accent">{{ $message }}</p>
            @enderror
        </div>

        <div class="sm:col-span-3">
            <x-submit-button loadingText="Sending…"
                class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90">
                Send invitation
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
@endsection
