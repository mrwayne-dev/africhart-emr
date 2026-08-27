<?php

namespace App\Http\Requests;

use App\Enums\StaffRole;
use App\Models\Staff;
use App\Models\StaffInvitation;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An admin issuing an invitation. The route is already role:admin-gated.
 *
 * Both uniqueness checks are scoped to the CURRENT tenant for free: `staff` and
 * `staff_invitations` exist only in the clinic's own database, so an address in
 * use at another clinic is not in use here.
 */
class SendInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role middleware gates this route
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],

            'email' => [
                'required', 'string', 'email', 'max:255',

                /*
                 * Catches DEACTIVATED staff too, which matters: a soft-deleted
                 * staff member still occupies the unique index on staff.email.
                 * Without this the invite would validate, get sent, and then
                 * fail with an integrity error at acceptance — after the person
                 * had clicked through and chosen a password. (No withTrashed()
                 * needed: the unique rule queries the table through a plain
                 * query builder, so Eloquent's soft-delete scope never applies
                 * and trashed rows are already visible to it.)
                 */
                Rule::unique(Staff::class, 'email'),

                /*
                 * One open invitation per address. Expired, revoked and already
                 * accepted rows are excluded so the same person CAN be invited
                 * again after an invite lapses.
                 *
                 * A closure, not ->where(): DatabaseRule::where() builds an
                 * equality comparison only, so `where('expires_at', '>', now())`
                 * would silently become `expires_at = '>'` and match nothing —
                 * making every re-invite look unique and defeating the check.
                 */
                Rule::unique(StaffInvitation::class, 'email')->where(
                    fn (Builder $query) => $query
                        ->whereNull('accepted_at')
                        ->whereNull('revoked_at')
                        ->where('expires_at', '>', now())
                ),
            ],

            'role' => ['required', Rule::enum(StaffRole::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'That email already has an account or an open invitation at this clinic.',
            'email.required' => 'Enter the email address to invite.',
            'role.required' => 'Choose which role this person will have.',
        ];
    }
}
