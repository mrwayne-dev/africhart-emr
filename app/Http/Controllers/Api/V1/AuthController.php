<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseController
{
    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Authenticate and receive an API token",
     *     tags={"Auth"},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"email","password"},
     *
     *         @OA\Property(property="email", type="string", format="email", example="doctor@africhart.com"),
     *         @OA\Property(property="password", type="string", format="password", example="password")
     *     )),
     *
     *     @OA\Response(response=200, description="Authenticated — returns user + token"),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->error('Invalid credentials.', 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->success([
            'user' => (new UserResource($user))->resolve(),
            'token' => $token,
        ], 'Authenticated successfully.');
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="Revoke the current API token",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(response=200, description="Logged out")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success([], 'Logged out.');
    }

    /**
     * @OA\Get(
     *     path="/auth/user",
     *     summary="Get the authenticated user",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(response=200, description="Current user"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function user(Request $request): JsonResponse
    {
        return $this->success((new UserResource($request->user()))->resolve());
    }
}
