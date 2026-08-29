@extends('layouts.marketing')

@section('title', 'Terms of service — AfriChart')
@section('description', 'The terms under which Nigerian clinics use AfriChart: subscriptions and billing, uptime and support, ownership of clinical data, and how either side can end the agreement.')

@section('content')
    <x-legal-page
        title="Terms of service"
        updated="22 August 2026"
        summary="These terms govern your clinic's use of AfriChart. They are written to be read once and understood — the parts that matter most to a clinic are ownership of your records, what happens to them if you leave, and what we are and are not promising about availability."
        :sections="[
            [
                'heading' => 'The agreement',
                'body' => [
                    'This agreement is between AfriChart Technologies Limited (RC 9782826), a company incorporated in Nigeria under the Companies and Allied Matters Act 2020, of Port Harcourt, Rivers State, and the clinic that subscribes to AfriChart. By creating a clinic workspace or using the service, you accept these terms.',
                    'The person who signs up must be authorised to bind the clinic. If you are signing up on behalf of a practice you do not own, make sure whoever does own it has agreed.',
                ],
            ],
            [
                'heading' => 'What the service is',
                'body' => [
                    'AfriChart is clinic-management software: patient records, a check-in queue, consultation notes, prescriptions, a drug catalogue, billing, and an audit trail, delivered over the web at your clinic\'s own subdomain.',
                    'It is a record-keeping and workflow tool. It does not practise medicine. It does not diagnose, prescribe, or advise on treatment, and nothing it displays is a clinical recommendation. Every clinical decision made using AfriChart remains the professional judgement and responsibility of the licensed practitioner making it.',
                ],
            ],
            [
                'heading' => 'Your account and your staff',
                'body' => [
                    'You create accounts for your own staff and assign each one a role. You are responsible for who you give access to, for what role you give them, and for removing accounts when someone leaves. We will act on an instruction from an account holder with the appropriate role; we cannot know that an account should no longer exist unless you tell us.',
                ],
                'list' => [
                    'Keep credentials confidential and do not share logins between staff — a shared login makes the audit trail meaningless.',
                    'Tell us promptly if you believe an account has been compromised.',
                    'Do not attempt to access another clinic\'s data, probe the platform for vulnerabilities without our written agreement, or resell access to the service.',
                ],
            ],
            [
                'heading' => 'Your data belongs to you',
                'body' => [
                    'Your patient records, your clinic\'s data, and everything you enter into AfriChart remain yours. We claim no ownership of it and no licence to it beyond what is needed to run the service for you — storing it, backing it up, transmitting it to your own staff, and restoring it when you ask.',
                    'You can export your data at any time while your subscription is active. If you leave, you have 30 days to export before the data is closed to access, and it is permanently deleted after 90 days as set out in the privacy policy.',
                    'We will not hold your data hostage over a billing dispute. Access to export survives non-payment.',
                ],
            ],
            [
                'heading' => 'Fees, billing and price changes',
                'body' => [
                    'Subscriptions are billed monthly in Nigerian naira, in advance, through Paystack. Setup fees are one-off and charged at the start. Prices shown on our pricing page are current at the time of publication.',
                    'If a payment fails we will tell you and retry. If it remains unpaid after 14 days we may suspend access to the workspace — your data is retained and exportable during suspension, and restored in full when the account is brought current.',
                    'We will give you at least 30 days\' notice by email before any price increase takes effect on your subscription. If you do not accept it, you may cancel before it applies.',
                ],
            ],
            [
                'heading' => 'Availability and support',
                'body' => [
                    'We aim for the service to be available at all times, and we monitor it. We do not offer a contractual uptime guarantee, and we would rather say so than publish a number we cannot yet stand behind. Planned maintenance is announced in advance and scheduled outside typical clinic hours wherever possible.',
                    'Support is provided by email, in English, from Port Harcourt. Clinic and Group plans receive priority handling. Support covers the use and operation of AfriChart; it does not extend to your clinic\'s hardware, internet connection, or third-party software.',
                    'Nigerian power and connectivity being what they are, AfriChart is built so that a dropped connection does not lose a record in progress. That is a design commitment, not a warranty.',
                ],
            ],
            [
                'heading' => 'Your obligations as a clinic',
                'body' => [
                    'You are the data controller for your patient records. That carries obligations we cannot discharge for you.',
                ],
                'list' => [
                    'Obtain whatever consent or lawful basis you need to record and process your patients\' health data.',
                    'Keep your records accurate, and correct them when a patient asks.',
                    'Comply with the NDPA, with the record-keeping rules of the Medical and Dental Council of Nigeria and any other body you are registered with, and with any state-level requirements that apply to your practice.',
                    'Respond to your own patients\' data-subject requests. We will help you find and export what you need.',
                ],
            ],
            [
                'heading' => 'Liability',
                'body' => [
                    'We provide the service with reasonable skill and care. To the extent the law allows, our total liability to you in any twelve-month period is limited to the subscription fees you paid us in that period.',
                    'We are not liable for clinical outcomes, for decisions made by your practitioners, for loss arising from your own staff\'s use or misuse of the system, or for indirect or consequential loss.',
                    'Nothing in these terms limits liability for death or personal injury caused by negligence, for fraud, or for anything else that cannot lawfully be limited.',
                ],
            ],
            [
                'heading' => 'Ending the agreement',
                'body' => [
                    'You may cancel at any time, effective at the end of your current billing month. There is no cancellation fee and no minimum term. Setup fees already paid are not refundable, as the work has been done.',
                    'We may suspend or end the agreement if fees remain unpaid after notice, if the service is used unlawfully or in a way that endangers the platform or other clinics, or if we cease to offer AfriChart — in which case we will give you at least 90 days\' notice and full assistance in exporting your data.',
                ],
            ],
            [
                'heading' => 'Changes, governing law, and contact',
                'body' => [
                    'We may update these terms. Material changes are emailed to your registered address at least 30 days before they take effect; continuing to use the service after that date means you accept them.',
                    'This agreement is governed by the laws of the Federal Republic of Nigeria, and the courts of Rivers State have jurisdiction. We would far rather resolve a disagreement by talking about it first.',
                    'Questions about these terms: legal@africhartemr.com.',
                ],
            ],
        ]" />
@endsection
