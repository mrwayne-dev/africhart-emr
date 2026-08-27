@extends('layouts.guest')

@section('title', 'Invitation not valid — AfriChart EMR')

{{-- The URL contains a single-use token: never canonicalised, never indexed. --}}
@section('private-url', '1')

@section('content')
    {{--
        ONE page for every way an invitation can fail: unknown, expired, already
        used, revoked, or issued by a different clinic.

        The wording must stay non-committal. Saying "this invitation has already
        been used" would confirm that the token is real and that someone at this
        named clinic holds an account — enough, with a list of tokens, to map
        staff to clinics. Nothing here should narrow down which case applies.
    --}}
    <div class="bg-page border border-line rounded-card p-8 text-center">
        <div class="w-12 h-12 bg-warm rounded-full flex items-center justify-center mx-auto mb-4">
            <x-phosphor-link-break class="w-6 h-6 text-ink" aria-hidden="true" />
        </div>

        <h1 class="text-xl font-medium text-ink tracking-tight">This invite link is not valid</h1>

        <p class="text-sm text-muted mt-2 leading-relaxed">
            It may have expired or already been used. Ask your clinic administrator
            to send you a new one.
        </p>

        <a href="{{ route('login') }}"
            class="inline-flex items-center gap-1.5 mt-6 text-sm text-ink font-medium hover:underline">
            Go to sign in
            <x-phosphor-arrow-right class="w-4 h-4" aria-hidden="true" />
        </a>
    </div>
@endsection
