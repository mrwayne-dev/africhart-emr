@props(['compact' => false])

{{--
    The issuing clinic's identity, for documents a patient receives.

    This exists because the on-screen invoice had NO clinic identity at all
    while carrying a Print button — so the two paths to the same patient-facing
    document disagreed: "Download PDF" produced a properly headed invoice and
    "Print" produced one that named nobody. The PDF was fixed in A2; this is the
    other path.

    Reads ClinicIdentity rather than taking the values as props, so a screen
    cannot forget to pass them and quietly fall back to the vendor's name.
--}}
@php
    $clinicName = \App\Support\ClinicIdentity::name();
    $clinicAddress = \App\Support\ClinicIdentity::address();
    $clinicPhone = \App\Support\ClinicIdentity::phone();
    $clinicEmail = \App\Support\ClinicIdentity::email();
    $contact = collect([$clinicPhone, $clinicEmail])->filter()->implode(' · ');
@endphp

<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <p class="{{ $compact ? 'text-base' : 'text-lg' }} font-medium text-ink tracking-tight">
        {{ $clinicName }}
    </p>

    @if ($clinicAddress)
        <p class="text-sm text-muted mt-0.5">{{ $clinicAddress }}</p>
    @endif

    @if ($contact)
        <p class="text-sm text-muted">{{ $contact }}</p>
    @endif
</div>
