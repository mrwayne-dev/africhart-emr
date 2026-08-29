@extends('layouts.marketing')

@section('title', 'Data processing agreement — AfriChart')
@section('description', 'The NDPA data processing agreement between AfriChart and clinics: subject matter, our obligations as processor, security measures, sub-processors, and assistance with data-subject requests.')

@section('content')
    <x-legal-page
        title="Data processing agreement"
        updated="22 August 2026"
        summary="Your clinic is the data controller for its patient records. We are your processor. This agreement sets out the instructions we act on, the security we apply, who else touches the data, and what we owe you when something goes wrong. It forms part of the terms of service and applies automatically to every clinic."
        :sections="[
            [
                'heading' => 'Parties and roles',
                'body' => [
                    'The clinic subscribing to AfriChart is the data controller. AfriChart Technologies Limited (RC 9782826), of Port Harcourt, Rivers State, Nigeria, is the data processor.',
                    'This agreement is entered into under the Nigeria Data Protection Act 2023. It takes effect when your clinic workspace is created and lasts as long as we hold personal data on your behalf. No signature is needed; if your clinic requires a countersigned copy for its own records, email us and we will provide one.',
                ],
            ],
            [
                'heading' => 'Subject matter and duration',
                'body' => [
                    'Subject matter: the provision of the AfriChart clinic-management platform.',
                    'Duration: for the term of your subscription, plus the retention periods set out in the privacy policy.',
                    'Nature and purpose: storing, organising, retrieving, transmitting, backing up and — on instruction — erasing personal data, so that your clinic can run its patient records, queue, consultations, prescriptions and billing.',
                ],
            ],
            [
                'heading' => 'Categories of data and data subjects',
                'body' => ['We process the following on your behalf.'],
                'list' => [
                    'Data subjects: your patients, and your clinic\'s staff who hold accounts.',
                    'Patient personal data: name, date of birth, sex, phone number, address, next of kin, and the clinic-assigned patient identifier.',
                    'Special-category health data: vital signs, presenting complaints, examination findings, diagnoses, prescriptions and dispensing records, lab requests and results, and clinical notes.',
                    'Financial data: invoices, payments, and outstanding balances for patients; billing details for the clinic.',
                    'Staff data: name, email, phone, role, and the audit record of their activity in the system.',
                ],
            ],
            [
                'heading' => 'Our obligations as processor',
                'body' => ['We commit to the following, and each is something you can hold us to.'],
                'list' => [
                    'To process personal data only on your documented instructions. Your use of the platform is the primary instruction; anything beyond it we will ask about first.',
                    'To tell you if we believe an instruction breaches the NDPA, rather than silently carrying it out.',
                    'To ensure everyone we authorise to process the data is bound by confidentiality.',
                    'To implement the security measures set out below, and to keep them under review.',
                    'To assist you in meeting your own obligations — data-subject requests, breach notifications, and impact assessments.',
                    'To delete or return the data at the end of the agreement, as you choose.',
                    'To make available the information you need to demonstrate our compliance, and to allow audit as described below.',
                ],
            ],
            [
                'heading' => 'Security measures',
                'body' => [
                    'These are the measures in place today. If we materially weaken any of them, we will tell you; if we strengthen them, we will not.',
                ],
                'list' => [
                    'Encryption in transit over TLS for all connections to the platform.',
                    'Encrypted daily backups, held separately from the live system, with restores rehearsed rather than assumed to work.',
                    'Logical separation of each clinic\'s data, so that a staff account can only reach its own clinic\'s records.',
                    'Role-based access control within the clinic, so that a receptionist, nurse, doctor, pharmacist and administrator each see only what their role requires.',
                    'A timestamped, immutable audit log of who viewed or changed which record, retained for the life of the account.',
                    'Administrative access limited to the engineers who need it to operate the platform, and logged when exercised.',
                    'Rate limiting and account lockout on authentication endpoints.',
                ],
            ],
            [
                'heading' => 'Sub-processors',
                'body' => [
                    'We engage the following sub-processors. Each is bound by terms no less protective than these.',
                ],
                'list' => [
                    'Our hosting and infrastructure provider — stores and serves the platform and its backups.',
                    'Paystack Payments Limited (Nigeria) — processes clinic subscription payments. Receives clinic billing data only; never patient data.',
                    'Our transactional email provider — delivers account and notification email. Receives recipient addresses and message contents.',
                ],
            ],
            [
                'heading' => 'Changes to sub-processors',
                'body' => [
                    'We will give you at least 30 days\' notice by email before adding or replacing a sub-processor.',
                    'If you object on reasonable data-protection grounds, tell us within those 30 days and we will work with you to find an alternative. If we cannot, you may terminate the affected part of the service without penalty and export your data in full.',
                ],
            ],
            [
                'heading' => 'International transfers',
                'body' => [
                    'Personal data is stored on infrastructure we control. Where any sub-processor operates outside Nigeria, we ensure an adequate transfer mechanism is in place as required by the NDPA before any personal data reaches them, and we tell you which sub-processor that applies to on request.',
                ],
            ],
            [
                'heading' => 'Assisting you with data-subject requests',
                'body' => [
                    'If a patient exercises a right — access, correction, erasure, restriction, objection or portability — the request is yours to answer, because the record is yours. Much of it you can handle directly in the platform: patient records are editable and exportable by your administrators without needing us.',
                    'Where you need more, we will help. If a patient contacts us directly, we will not act on the record; we will forward the request to your clinic and tell the patient we have done so.',
                ],
            ],
            [
                'heading' => 'Breach notification',
                'body' => [
                    'If we become aware of a personal data breach affecting your data, we will notify you without undue delay and in any case within 72 hours of becoming aware of it.',
                    'The notification will describe the nature of the breach, the categories and approximate number of data subjects and records affected, the likely consequences, and the measures taken or proposed. Where we do not yet know something, we will say so and follow up rather than delay the first notification.',
                    'Notifying the Nigeria Data Protection Commission and your affected patients is your responsibility as controller. We will provide whatever information and assistance you need to do it properly.',
                ],
            ],
            [
                'heading' => 'Audit',
                'body' => [
                    'On reasonable written notice, and no more than once a year unless a breach or a regulator requires otherwise, you may request information demonstrating our compliance with this agreement. We will respond with documentation, and where documentation is genuinely insufficient we will accommodate an inspection, arranged so that it does not compromise the security or confidentiality of other clinics\' data.',
                ],
            ],
            [
                'heading' => 'Return and deletion',
                'body' => [
                    'At the end of the agreement you may export your data in full. Tell us whether you want it returned or deleted; if you say nothing, the retention periods in the privacy policy apply — 30 days\' export access, permanent deletion from live systems at 90 days, and expiry from encrypted backups within a further 30 days.',
                    'Where we are required by Nigerian law to retain something, we will keep only that, keep it only for as long as required, and tell you what it is.',
                ],
            ],
            [
                'heading' => 'Contact',
                'body' => [
                    'For anything under this agreement, including a request for a countersigned copy, email legal@africhartemr.com.',
                ],
            ],
        ]" />
@endsection
