@extends('layouts.marketing')

@section('title', 'Get started — AfriChart')

@section('content')
    {{-- PLACEHOLDER — the real form is built in its own step. The Sign in link
         below is not placeholder content: "Sign in" was removed from the nav on
         the understanding that this page carries it, so it must exist now. --}}
    <x-marketing-section tone="page">
        <x-marketing-heading eyebrow="Coming next" title="Get started" lead="This page is being built." />

        <p class="text-sm text-muted">
            Already have an account?
            <a href="{{ route('login') }}" class="text-ink font-medium hover:underline">Sign in</a>
        </p>
    </x-marketing-section>
@endsection
