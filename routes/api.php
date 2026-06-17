<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConsultationController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\PatientController;
use App\Http\Controllers\Api\V1\PrescriptionController;
use App\Http\Controllers\Api\V1\QueueController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['success' => true, 'status' => 'ok', 'service' => config('app.name')]);
});

Route::prefix('v1')->group(function () {
    // --- Public ---
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    // --- Authenticated (Sanctum token) ---
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/user', [AuthController::class, 'user']);

        // Patients — all roles
        Route::middleware('role:admin,doctor,nurse,receptionist')->group(function () {
            Route::get('/patients', [PatientController::class, 'index']);
            Route::post('/patients', [PatientController::class, 'store']);
            Route::get('/patients/{patient}', [PatientController::class, 'show']);
            Route::put('/patients/{patient}', [PatientController::class, 'update']);
        });
        Route::middleware('role:admin,doctor')
            ->get('/patients/{patient}/timeline', [PatientController::class, 'timeline']);

        // Consultations — admin, doctor, nurse (policies enforce finer rules)
        Route::middleware('role:admin,doctor,nurse')->group(function () {
            Route::get('/consultations', [ConsultationController::class, 'index']);
            Route::get('/consultations/{consultation}', [ConsultationController::class, 'show']);
            Route::patch('/consultations/{consultation}/vitals', [ConsultationController::class, 'vitals']);
            Route::get('/consultations/{consultation}/prescriptions', [PrescriptionController::class, 'index']);
        });
        Route::middleware('role:admin,doctor')->group(function () {
            Route::post('/consultations', [ConsultationController::class, 'store']);
            Route::put('/consultations/{consultation}', [ConsultationController::class, 'update']);
            Route::patch('/consultations/{consultation}/complete', [ConsultationController::class, 'complete']);
            Route::post('/consultations/{consultation}/prescriptions', [PrescriptionController::class, 'store']);
            Route::delete('/prescriptions/{prescription}', [PrescriptionController::class, 'destroy']);
        });

        // Invoices — view (admin, doctor, receptionist); manage (admin, receptionist)
        Route::middleware('role:admin,doctor,receptionist')->group(function () {
            Route::get('/invoices', [InvoiceController::class, 'index']);
            Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
            Route::get('/invoices/{invoice}/pdf', [App\Http\Controllers\InvoiceController::class, 'downloadPdf']);
        });
        Route::middleware('role:admin,receptionist')->group(function () {
            Route::post('/invoices/from-consultation/{consultation}', [InvoiceController::class, 'generateFromConsultation']);
            Route::put('/invoices/{invoice}', [InvoiceController::class, 'update']);
            Route::patch('/invoices/{invoice}/pay', [InvoiceController::class, 'pay']);
        });

        // Queue — view all; mutate (admin, nurse, receptionist); cancel (admin, receptionist)
        Route::get('/queue', [QueueController::class, 'index']);
        Route::middleware('role:admin,nurse,receptionist')->group(function () {
            Route::post('/queue', [QueueController::class, 'store']);
            Route::patch('/queue/{queue}/assign', [QueueController::class, 'assign']);
        });
        Route::middleware('role:admin,receptionist')
            ->patch('/queue/{queue}/cancel', [QueueController::class, 'cancel']);

        // Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    });
});
