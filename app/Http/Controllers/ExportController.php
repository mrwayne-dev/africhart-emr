<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\Invoice;
use App\Models\Patient;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends BaseController
{
    public function patients(): StreamedResponse
    {
        $this->authorize('export-data');

        return $this->stream(
            'patients',
            ['Patient ID', 'Full Name', 'DOB', 'Age', 'Phone', 'Blood Group', 'Allergies', 'Registered By', 'Date'],
            Patient::with('registeredBy')->latest()->cursor(),
            fn (Patient $p) => [
                $p->patient_id, $p->full_name, $p->date_of_birth->format('Y-m-d'), $p->age,
                $p->phone, $p->blood_group->value, $p->allergies ?? '',
                $p->registeredBy?->name ?? '', $p->created_at->format('Y-m-d H:i'),
            ],
        );
    }

    public function consultations(): StreamedResponse
    {
        $this->authorize('export-data');

        return $this->stream(
            'consultations',
            ['Consultation ID', 'Patient', 'Doctor', 'Diagnosis', 'Status', 'Date'],
            Consultation::with(['patient', 'doctor'])->latest()->cursor(),
            fn (Consultation $c) => [
                $c->consultation_id, $c->patient?->full_name ?? '', $c->doctor?->name ?? '',
                $c->diagnosis ?? '', $c->status->label(), $c->created_at->format('Y-m-d H:i'),
            ],
        );
    }

    public function invoices(): StreamedResponse
    {
        $this->authorize('export-data');

        return $this->stream(
            'invoices',
            ['Invoice No.', 'Patient', 'Subtotal', 'Tax', 'Discount', 'Total', 'Status', 'Payment Method', 'Date'],
            Invoice::with('patient')->latest()->cursor(),
            fn (Invoice $i) => [
                $i->invoice_number, $i->patient?->full_name ?? '', $i->subtotal, $i->tax, $i->discount,
                $i->total, $i->status->label(), $i->payment_method?->label() ?? '', $i->created_at->format('Y-m-d H:i'),
            ],
        );
    }

    /**
     * Stream rows as a downloadable CSV.
     *
     * @param  array<int, string>  $headings
     * @param  iterable<int, mixed>  $rows
     * @param  callable(mixed): array<int, mixed>  $mapper
     */
    private function stream(string $name, array $headings, iterable $rows, callable $mapper): StreamedResponse
    {
        $filename = $name.'-'.now()->format('Y-m-d').'.csv';

        return response()->stream(function () use ($headings, $rows, $mapper) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headings);
            foreach ($rows as $row) {
                fputcsv($handle, $mapper($row));
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
