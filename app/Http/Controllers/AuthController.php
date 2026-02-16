<?php

namespace App\Http\Controllers;

use App\DTO\LoginDTO;
use App\DTO\RegisterDTO;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = RegisterDTO::fromArray($request->validated());
        [$user, $token] = $this->authService->register($dto);

        return UserResource::make($user)
            ->additional(['token' => $token])
            ->response()
            ->setStatusCode(201);
    }

    public function login(LoginRequest $request): UserResource
    {
        $dto = LoginDTO::fromArray($request->validated());
        [$user, $token] = $this->authService->login($dto);

        return UserResource::make($user)
            ->additional(['token' => $token]);
    }

    public function logout(Request $request): Response
    {
        $user = $request->user();
        $this->authService->logout($user);

        return response()->noContent();
    }
}
