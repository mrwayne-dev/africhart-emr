<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Consultation Fee
    |--------------------------------------------------------------------------
    |
    | Auto-added as the first line item when an invoice is generated from a
    | consultation. Clinics can edit the amount per-invoice; this is just the
    | starting value. In Naira.
    |
    */
    'consultation_fee' => (float) env('BILLING_CONSULTATION_FEE', 5000),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */
    'currency_symbol' => '₦',
];
