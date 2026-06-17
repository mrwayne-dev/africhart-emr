@extends('layouts.app')

@section('title', 'Staff — AfriChart EMR')
@section('page-title', 'Staff')
@section('page-subtitle', 'Team members with access to this clinic')

@section('content')
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
