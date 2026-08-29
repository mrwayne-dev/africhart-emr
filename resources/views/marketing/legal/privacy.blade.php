@extends('layouts.marketing')

@section('title', 'Privacy policy — AfriChart')
@section('description', 'How AfriChart handles clinic and patient data, the lawful basis for it, how long it is kept, and the rights available under the Nigeria Data Protection Act.')

@section('content')
    <x-legal-page
        title="Privacy policy"
        updated="22 August 2026"
        summary="AfriChart is clinic-management software. Clinics decide what patient data goes into it and why; we hold and protect that data on their instruction. This policy explains both halves — what we do with your clinic's own information, and what we do with the patient records you store with us."
        :sections="[
            [
                'heading' => 'Who we are',
                'body' => [
                    'AfriChart is operated by AfriChart Technologies Limited (RC 9782826), a company incorporated in Nigeria under the Companies and Allied Matters Act 2020 and based in Port Harcourt, Rivers State. We build and run the AfriChart clinic-management platform.',
                    'Under the Nigeria Data Protection Act 2023 (NDPA), the clinic using AfriChart is the data controller for its patient records — it decides what to collect and why. We are the data processor: we hold and process that data only on the clinic\'s instruction. For your clinic\'s own account and billing information, we are the controller.',
                ],
            ],
            [
                'heading' => 'What we collect',
                'body' => ['We collect three distinct categories, and it matters which is which.'],
                'list' => [
                    'Clinic account data — clinic name, subdomain, address, the names, email addresses, phone numbers and roles of the staff you create accounts for, and your billing records.',
                    'Patient data you enter — demographics, contact details, visit history, vital signs, diagnoses, prescriptions, lab requests and results, invoices and payments. This is health data, and it is sensitive personal data under the NDPA.',
                    'Technical and audit data — IP address, browser type, and a timestamped record of who viewed or changed which record. The audit log exists so that access to patient data can be accounted for; it is a safeguard, not analytics.',
                ],
            ],
            [
                'heading' => 'What we do not do',
                'body' => ['Stated plainly, because these are the questions clinics actually ask.'],
                'list' => [
                    'We do not sell patient data, clinic data, or any derivative of it. There is no circumstance in which we would.',
                    'We do not use your patient records to train machine-learning models.',
                    'We do not share patient data with advertisers, data brokers, or insurers.',
                    'We do not read patient records for our own purposes. Support staff access a clinic\'s data only when you ask us to investigate something, and that access is written to the audit log like any other.',
                ],
            ],
            [
                'heading' => 'Why we process it, and on what basis',
                'body' => [
                    'Clinic account data is processed to perform our contract with you — creating your workspace, authenticating your staff, issuing invoices, and providing support. Some of it we keep to meet Nigerian tax and company-records obligations.',
                    'Patient data is processed solely to provide the service to your clinic. Our lawful basis is the contract with you; your lawful basis for holding it is a matter between your clinic and your patients, and typically rests on the provision of healthcare and your professional record-keeping duties. We do not determine that basis on your behalf.',
                    'Audit and security data is processed on the basis of our legitimate interest in keeping the platform secure and accountable, and in support of your obligation to know who accessed a record.',
                ],
            ],
            [
                'heading' => 'Where it is stored, and who can reach it',
                'body' => [
                    'Data is stored on servers we control, with encrypted daily backups. Each clinic\'s records are separated from every other clinic\'s; staff accounts can only reach their own clinic\'s data.',
                    'Within your clinic, what a person can see is governed by their role. A receptionist does not see clinical notes. A pharmacist sees prescriptions but not the full consultation record. You control who holds which role.',
                    'A small number of our engineers hold administrative access, which is required to operate and back up the platform. It is limited to the people who need it and is logged.',
                ],
            ],
            [
                'heading' => 'Third parties we rely on',
                'body' => [
                    'We keep this list short deliberately, and we do not add to it quietly.',
                ],
                'list' => [
                    'Our hosting and infrastructure provider, which stores the data on our behalf.',
                    'Paystack, for payment processing. Paystack receives your clinic\'s billing details. It never receives patient data.',
                    'Our transactional email provider, which delivers account emails, password resets and notifications. It receives the recipient\'s email address and the contents of that message.',
                ],
            ],
            [
                'heading' => 'How long we keep it',
                'body' => [
                    'Patient records are kept for as long as your clinic keeps them. Deletion inside AfriChart is a soft delete by default — the record leaves normal views but remains recoverable, because an accidental deletion of a patient record is a serious clinical event. We can permanently erase records on your written instruction.',
                    'If your subscription ends, your data remains available for export for 30 days. After 90 days it is permanently deleted from live systems, and it ages out of encrypted backups within a further 30 days.',
                    'Audit logs are retained for as long as the associated clinic account exists, because their value is in the history they preserve.',
                    'Marketing enquiries — a demo request or sign-up form — are kept for 24 months from your last contact with us, then deleted.',
                ],
            ],
            [
                'heading' => 'Your rights under the NDPA',
                'body' => [
                    'You have the right to access the personal data we hold about you, to have it corrected, to have it erased, to restrict or object to its processing, and to receive it in a portable format. You may also lodge a complaint with the Nigeria Data Protection Commission.',
                    'If you are a patient at a clinic that uses AfriChart, your clinic is the controller of your record — please make the request to your clinic directly. If you contact us instead, we will pass it to them; we cannot act on a patient record without the clinic\'s instruction, because the record is theirs.',
                    'If you are a clinic, you can exercise these rights over your own account data by emailing us. We respond within 30 days.',
                ],
            ],
            [
                'heading' => 'Breach notification',
                'body' => [
                    'If we become aware of a breach affecting your data, we will notify you without undue delay and in any case within 72 hours of becoming aware of it, with what we know at the time: what happened, what data was involved, what we are doing about it, and what you should do.',
                    'We will also notify the Nigeria Data Protection Commission where the NDPA requires it. Notifying your affected patients is your clinic\'s obligation as controller — we will give you everything you need to do it.',
                ],
            ],
            [
                'heading' => 'Changes and contact',
                'body' => [
                    'If we change this policy in a way that materially affects how your data is handled, we will email the clinic\'s registered address before the change takes effect, not after.',
                    'For anything in this document, or to exercise a right, email legal@africhartemr.com.',
                ],
            ],
        ]" />
@endsection
