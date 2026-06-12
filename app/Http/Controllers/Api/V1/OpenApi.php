<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @OA\Info(
 *     title="AfriChart EMR API",
 *     version="1.0.0",
 *     description="REST API for the AfriChart EMR — patients, consultations, prescriptions, invoices and the patient queue. All endpoints (except login) require a Sanctum bearer token.",
 *
 *     @OA\Contact(name="Lymora Tech", email="michael@mgbah.dev")
 * )
 *
 * @OA\Server(url="/api/v1", description="API v1")
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Token",
 *     description="Sanctum personal access token returned by POST /auth/login."
 * )
 *
 * @OA\Tag(name="Auth", description="Authentication & tokens")
 * @OA\Tag(name="Patients", description="Patient records")
 * @OA\Tag(name="Consultations", description="Clinical consultations & vitals")
 * @OA\Tag(name="Prescriptions", description="Medications prescribed within a consultation")
 * @OA\Tag(name="Invoices", description="Billing & payments")
 * @OA\Tag(name="Queue", description="Daily patient queue")
 * @OA\Tag(name="Dashboard", description="Role-based statistics")
 */
abstract class OpenApi {}
