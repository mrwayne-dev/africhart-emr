<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\BaseController;
use App\Models\Medication;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Drug Catalogue — the clinic's own drug list, folded into the settings hub.
 *
 * The /drug-catalog page moved rather than rebuilt: same query, same
 * pagination, same create/edit/toggle actions on MedicationController. It
 * belongs beside the clinic's other configuration, because that is what it is
 * — the prices this clinic charges, not a clinical reference.
 *
 * The catalogue was already per-tenant before B4 touched it: `medications`
 * lives in the tenant migration set and is absent from central, so each clinic
 * has always had its own list at its own prices. A2 pinned that with a test
 * rather than rebuilding it, and this change does not alter the storage at all.
 */
class CatalogueController extends BaseController
{
    public function index(Request $request): View
    {
        $medications = Medication::query()
            ->when($request->input('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('settings.catalogue', compact('medications'));
    }
}
