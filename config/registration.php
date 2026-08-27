<?php

return [

    /*
    | The four REGISTER_CODE_* invite codes that used to live here are gone.
    |
    | They were one value per role for the WHOLE server, so the same admin code
    | worked at every clinic — a global credential with a per-clinic effect.
    | Staff now join through a per-clinic invitation in the tenant's own
    | `staff_invitations` table (App\Models\StaffInvitation).
    |
    | Deleted rather than left blank: a code read from config is a hole that
    | reopens the moment someone sets the value.
    */

    /*
    | How long an email verification code stays valid (minutes).
    */
    'verification_code_ttl' => 10,

];
