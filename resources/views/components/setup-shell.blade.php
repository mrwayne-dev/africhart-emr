@props(['step', 'progress', 'title', 'lead'])

{{--
    The first-run wizard's frame.

    Shows all three steps with the current one marked, so an owner can see how
    much is left — a wizard that reveals its length one screen at a time feels
    longer than it is.

    "Skip for now" is on every step on purpose. Everything here also lives in
    Settings, so leaving early costs nothing, and a setup flow with no exit is
    one people resent rather than complete.
--}}
<div class="max-w-3xl mx-auto">

    <ol class="flex items-center gap-2 mb-8" aria-label="Setup progress">
        @foreach ([1 => 'Clinic details', 2 => 'Your drug prices', 3 => 'Your team'] as $n => $label)
            @php $state = $n < $step ? 'done' : ($n === $step ? 'current' : 'todo'); @endphp
            <li class="flex items-center gap-2 {{ $n > 1 ? 'flex-1' : '' }}">
                @if ($n > 1)
                    <span class="h-px flex-1 {{ $state === 'todo' ? 'bg-line' : 'bg-ink' }}" aria-hidden="true"></span>
                @endif
                <span @class([
                    'inline-flex items-center gap-2 text-sm whitespace-nowrap',
                    'text-ink font-medium' => $state === 'current',
                    'text-muted' => $state !== 'current',
                ]) @if ($state === 'current') aria-current="step" @endif>
                    <span @class([
                        'w-6 h-6 rounded-full inline-flex items-center justify-center text-xs shrink-0',
                        'bg-ink text-white' => $state !== 'todo',
                        'bg-warm text-muted' => $state === 'todo',
                    ])>
                        @if ($state === 'done')
                            <x-phosphor-check class="w-3.5 h-3.5" aria-hidden="true" />
                        @else
                            {{ $n }}
                        @endif
                    </span>
                    <span class="hidden sm:inline">{{ $label }}</span>
                </span>
            </li>
        @endforeach
    </ol>

    <div class="bg-page border border-line rounded-card">
        <div class="px-6 py-5 border-b border-line">
            <h1 class="text-lg font-medium text-ink tracking-tight">{{ $title }}</h1>
            <p class="text-sm text-muted mt-1 leading-relaxed">{{ $lead }}</p>
        </div>

        <div class="p-6">
            {{ $slot }}
        </div>
    </div>

    <div class="flex items-center justify-between mt-5">
        <form method="POST" action="{{ route('setup.complete') }}">
            @csrf
            <button type="submit" class="text-sm text-muted hover:text-ink transition-colors">
                Skip for now — I'll do this in Settings
            </button>
        </form>
    </div>
</div>
