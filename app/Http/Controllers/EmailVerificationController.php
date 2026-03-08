<?php

namespace App\Http\Controllers;

use App\Exceptions\UnknownException;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EmailVerificationController extends Controller
{
    public function __construct(
        private EmailVerificationService $emailVerificationService,
    ) {}

    public function verify(int $id, string $hash): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            throw new UnknownException();
        }

        $this->emailVerificationService->verify($user, $hash);

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully.',
        ]);
    }

    public function resend(Request $request): JsonResponse
    {
        $this->emailVerificationService->resend($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Verification link sent.',
        ]);
    }
}
