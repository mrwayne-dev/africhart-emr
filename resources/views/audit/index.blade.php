@extends('layouts.app')

@section('title', 'Audit Log — AfriChart EMR')
@section('page-title', 'Audit Log')
@section('page-subtitle', 'Who did what, and when')

@section('content')
    {{-- Filters --}}
    <form method="GET" action="{{ route('audit.index') }}" class="flex flex-col sm:flex-row gap-3 mb-6">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description or user…"
            class="flex-1 bg-warm rounded text-sm text-ink-body px-4 py-2.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
        <select name="model_type"
            class="sm:w-44 bg-warm rounded text-sm text-ink-body px-4 py-2.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
            <option value="">All records</option>
            @foreach ($modelTypes as $type)
                <option value="{{ $type }}" @selected(request('model_type') === $type)>{{ class_basename($type) }}</option>
            @endforeach
        </select>
        <select name="user_id"
            class="sm:w-44 bg-warm rounded text-sm text-ink-body px-4 py-2.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
            <option value="">All users</option>
            @foreach ($users as $u)
                <option value="{{ $u->id }}" @selected((string) request('user_id') === (string) $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90 transition-colors">Filter</button>
    </form>

    <div class="bg-page border border-line rounded-card">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-muted border-b border-line whitespace-nowrap">
                        <th class="px-4 py-3 font-medium whitespace-nowrap">When</th>
                        <th class="px-4 py-3 font-medium">User</th>
                        <th class="px-4 py-3 font-medium">Action</th>
                        <th class="px-4 py-3 font-medium">Record</th>
                        <th class="px-4 py-3 font-medium">Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-b border-line last:border-0 even:bg-warm/60">
                            <td class="px-4 py-3 text-muted whitespace-nowrap">{{ $log->created_at?->format('j M Y, g:i A') }}</td>
                            <td class="px-4 py-3 text-ink">{{ $log->user_name }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full
                                    @class([
                                        'bg-emerald-100 text-emerald-700' => $log->action === 'created',
                                        'bg-blue-100 text-blue-700' => $log->action === 'updated',
                                        'bg-red-100 text-red-700' => $log->action === 'deleted',
                                    ])">{{ ucfirst($log->action) }}</span>
                            </td>
                            <td class="px-4 py-3 text-muted">{{ class_basename($log->model_type) }} #{{ $log->model_id }}</td>
                            <td class="px-4 py-3 text-ink-body">{{ $log->description }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-muted">No audit entries found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($logs->hasPages())
        <div class="mt-6">{{ $logs->links() }}</div>
    @endif
@endsection
