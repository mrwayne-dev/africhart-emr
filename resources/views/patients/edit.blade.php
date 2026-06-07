@extends('layouts.app')

@section('title', 'Edit Patient — AfriChart EMR')
@section('page-title', 'Edit Patient')
@section('page-subtitle', $patient->patient_id)

@section('content')
    <div class="max-w-2xl">
        <div class="bg-page border border-line rounded-card p-8">
            <form method="POST" action="{{ route('patients.update', $patient) }}">
                @csrf
                @method('PUT')

                @include('patients.form', ['patient' => $patient])

                <div class="flex items-center gap-4 mt-8">
                    <button type="submit"
                        class="bg-ink text-white rounded-full px-6 py-3 text-sm font-medium hover:bg-ink/90 transition-colors">
                        Save Changes
                    </button>
                    <a href="{{ route('patients.show', $patient) }}" class="text-sm text-muted hover:text-ink transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
