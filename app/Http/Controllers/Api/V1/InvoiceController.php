<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\MarkInvoicePaidRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Consultation;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends BaseController
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    /**
     * @OA\Get(
     *     path="/invoices",
     *     summary="List invoices (paginated)",
     *     tags={"Invoices"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"draft","issued","paid","partially_paid","cancelled"})),
     *
     *     @OA\Response(response=200, description="Paginated invoice list"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = $this->invoiceService->getInvoiceList(
            search: $request->input('search'),
            status: $request->input('status'),
        );

        $invoices->through(fn (Invoice $i) => (new InvoiceResource($i->loadMissing('patient')))->resolve());

        return $this->paginated($invoices);
    }

    /**
     * @OA\Get(
     *     path="/invoices/{invoice}",
     *     summary="Show an invoice with line items",
     *     tags={"Invoices"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="invoice", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Invoice detail"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        $invoice->load(['patient', 'createdBy', 'items']);

        return $this->success((new InvoiceResource($invoice))->resolve());
    }

    /**
     * @OA\Post(
     *     path="/invoices/from-consultation/{consultation}",
     *     summary="Generate an invoice from a consultation",
     *     tags={"Invoices"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="consultation", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=201, description="Invoice generated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function generateFromConsultation(Consultation $consultation): JsonResponse
    {
        $this->authorize('create', Invoice::class);

        if ($consultation->invoice) {
            return $this->success(
                (new InvoiceResource($consultation->invoice->load('items')))->resolve(),
                'This consultation already has an invoice.',
            );
        }

        if ($consultation->status !== ConsultationStatus::Completed) {
            return $this->error('An invoice can only be generated once the consultation is completed.', 422);
        }

        $invoice = $this->invoiceService->generateFromConsultation($consultation, request()->user()->id);

        return $this->success(
            (new InvoiceResource($invoice->load(['patient', 'items'])))->resolve(),
            'Invoice generated.',
            201,
        );
    }

    /**
     * @OA\Put(
     *     path="/invoices/{invoice}",
     *     summary="Update invoice tax / discount / notes",
     *     tags={"Invoices"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="invoice", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(@OA\JsonContent(
     *
     *         @OA\Property(property="tax", type="number"),
     *         @OA\Property(property="discount", type="number"),
     *         @OA\Property(property="notes", type="string")
     *     )),
     *
     *     @OA\Response(response=200, description="Updated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        $invoice->update($request->validated());
        $this->invoiceService->recalculateTotals($invoice);

        return $this->success((new InvoiceResource($invoice->fresh(['items'])))->resolve(), 'Invoice updated.');
    }

    /**
     * @OA\Patch(
     *     path="/invoices/{invoice}/pay",
     *     summary="Mark an invoice as paid",
     *     tags={"Invoices"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="invoice", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"payment_method"},
     *
     *         @OA\Property(property="payment_method", type="string", enum={"cash","transfer","card","insurance","other"})
     *     )),
     *
     *     @OA\Response(response=200, description="Marked paid"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function pay(MarkInvoicePaidRequest $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('markPaid', $invoice);

        $invoice = $this->invoiceService->markAsPaid($invoice, $request->validated()['payment_method']);

        return $this->success((new InvoiceResource($invoice))->resolve(), 'Invoice marked as paid.');
    }
}
