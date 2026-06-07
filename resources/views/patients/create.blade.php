@extends('layouts.app')

@section('title', 'Register Patient — AfriChart EMR')
@section('page-title', 'Register New Patient')
@section('page-subtitle', 'Add a patient to the clinic records')

@section('content')
    <div class="max-w-2xl">
        <div class="bg-page border border-line rounded-card p-8">
            <form method="POST" action="{{ route('patients.store') }}">
                @csrf

                @include('patients.form')

                <div class="flex items-center gap-4 mt-8">
                    <button type="submit"
                        class="bg-ink text-white rounded-full px-6 py-3 text-sm font-medium hover:bg-ink/90 transition-colors">
                        Register Patient
                    </button>
                    <a href="{{ route('patients.index') }}" class="text-sm text-muted hover:text-ink transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
