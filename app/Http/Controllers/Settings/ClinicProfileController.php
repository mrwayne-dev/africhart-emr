<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\BaseController;
use App\Http\Requests\ClinicProfileRequest;
use App\Models\Consultation;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Clinic Profile — the clinic's own details.
 *
 * ── Where each field lives, and why it is split ────────────────────────────
 *
 * `name` and `id_prefix` are CENTRAL, on the `clinics` row. Everything else is
 * a tenant setting.
 *
 * That split is not arbitrary. Both central fields are IDENTITY: the name is
 * what the registry calls this clinic and what `tenant('name')` returns
 * everywhere, and the prefix carries a cross-clinic UNIQUE index that nothing
 * inside one tenant's database could enforce. Copying either into tenant
 * settings would create a second answer to "what is this clinic called",
 * and the two would drift the first time one was edited — the same reason D14
 * keeps subscription status central rather than pushing it into tenant DBs.
 *
 * The address, phone, email, fee and timezone need no cross-clinic guarantee
 * and belong beside the clinic's other operational settings.
 */
class ClinicProfileController extends BaseController
{
    public function edit(): View
    {
        return view('settings.profile', [
            'clinic' => tenant(),
            'canEditPrefix' => $this->canEditPrefix(),
            'recordCount' => $this->recordCount(),
            'timezones' => $this->timezoneOptions(),
        ]);
    }

    public function update(ClinicProfileRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $clinic = tenant();

        /*
         * Central first, and only the fields that belong there.
         *
         * The Clinic model's own saving guards still apply — the subdomain
         * blocklist and the prefix format check both live there, so a bad
         * value raises rather than being written. That is deliberate: this
         * controller is not the only way a clinic is edited, and the model is.
         */
        $clinic->name = $data['name'];

        if ($this->canEditPrefix() && isset($data['id_prefix'])) {
            $clinic->id_prefix = $data['id_prefix'];
        }

        $clinic->save();

        foreach ([
            Setting::CLINIC_ADDRESS => $data['address'] ?? null,
            Setting::CLINIC_PHONE => $data['phone'] ?? null,
            Setting::CLINIC_EMAIL => $data['email'] ?? null,
            Setting::CONSULTATION_FEE => $data['consultation_fee'],
            Setting::CLINIC_TIMEZONE => $data['timezone'],
        ] as $key => $value) {
            Setting::put($key, $value);
        }

        return back()->with('success', 'Clinic profile updated.');
    }

    /**
     * The identifier prefix may only change while the clinic has minted no
     * identifiers.
     *
     * It is stamped into every patient ID, consultation ID and invoice number
     * already issued, and those are printed on documents patients keep and
     * quoted down the phone in support. Changing it later does not renumber
     * them — it strands them under a prefix the clinic no longer uses, which is
     * a worse outcome than an unchangeable field.
     *
     * The same reasoning closed the Sprint-2 collision gate: identifiers are
     * cheap to get right before records exist and expensive afterwards.
     */
    protected function canEditPrefix(): bool
    {
        return $this->recordCount() === 0;
    }

    protected function recordCount(): int
    {
        // withTrashed: a soft-deleted patient still holds an issued ID.
        return Patient::withTrashed()->count()
            + Consultation::count()
            + Invoice::count();
    }

    /**
     * Nigerian zones first, then everything else.
     *
     * Every clinic is in Nigeria today (D7 scopes the product to Nigerian
     * clinics), so burying Africa/Lagos in an alphabetical list of 400+ zones
     * would be perverse. The full list stays available rather than being
     * hard-coded to one entry: a clinic group with a site outside the country
     * is a plausible future, and a select that cannot express it would send
     * someone to the database.
     *
     * @return array<string, string>
     */
    protected function timezoneOptions(): array
    {
        $nigerian = ['Africa/Lagos' => 'Africa/Lagos (WAT, UTC+1) — Nigeria'];

        $rest = [];

        foreach (timezone_identifiers_list() as $zone) {
            if ($zone !== 'Africa/Lagos') {
                $rest[$zone] = $zone;
            }
        }

        return $nigerian + $rest;
    }
}
