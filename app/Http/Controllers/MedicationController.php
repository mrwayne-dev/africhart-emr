<?php

namespace App\Http\Controllers;

use App\Models\Medication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicationController extends BaseController
{
    public function index(Request $request): View
    {
        $medications = Medication::query()
            ->when($request->input('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('drug-catalog.index', compact('medications'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateMedication($request);

        Medication::create($data + ['is_active' => true]);

        return back()->with('success', 'Medication added to the catalog.');
    }

    public function update(Request $request, Medication $medication): RedirectResponse
    {
        $data = $this->validateMedication($request, $medication);

        $medication->update($data);

        return back()->with('success', 'Medication updated.');
    }

    public function toggle(Medication $medication): RedirectResponse
    {
        $medication->update(['is_active' => ! $medication->is_active]);

        return back()->with('success', $medication->is_active
            ? 'Medication re-activated.'
            : 'Medication deactivated — it will no longer appear in autocomplete.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateMedication(Request $request, ?Medication $medication = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:medications,name'.($medication ? ",{$medication->id}" : '')],
            'default_price' => ['required', 'numeric', 'min:0'],
            'common_frequency' => ['nullable', 'string', 'max:255'],
            'dosages' => ['nullable', 'string', 'max:255'],
            'routes' => ['nullable', 'string', 'max:255'],
        ]);

        // Comma-separated text fields → arrays for the json columns.
        $validated['dosages'] = $this->splitList($validated['dosages'] ?? null);
        $validated['routes'] = $this->splitList($validated['routes'] ?? null);

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    private function splitList(?string $value): array
    {
        return collect(explode(',', (string) $value))
            ->map(fn ($v) => trim($v))
            ->filter()
            ->values()
            ->all();
    }
}
