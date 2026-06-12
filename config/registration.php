<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Registration invite codes
    |--------------------------------------------------------------------------
    |
    | A user may only self-register for a role if they supply the matching
    | invite code. The code both gates access and determines the role, so no
    | one can register as an admin without the admin code. Codes live in .env.
    |
    */

    'codes' => [
        'admin' => env('REGISTER_CODE_ADMIN'),
        'doctor' => env('REGISTER_CODE_DOCTOR'),
        'nurse' => env('REGISTER_CODE_NURSE'),
        'receptionist' => env('REGISTER_CODE_RECEPTIONIST'),
    ],

    /*
    | How long an email verification code stays valid (minutes).
    */
    'verification_code_ttl' => 10,

];
