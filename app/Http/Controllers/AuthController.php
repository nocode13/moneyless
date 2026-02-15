<?php

namespace App\Http\Controllers;

use App\DTO\LoginDTO;
use App\DTO\RegisterDTO;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\AuthResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = RegisterDTO::fromArray($request->validated());

        return $this->authService
            ->register($dto)
            ->response()
            ->setStatusCode(201);
    }

    public function login(LoginRequest $request): AuthResource
    {
        $dto = LoginDTO::fromArray($request->validated());

        return $this->authService->login($dto);
    }

    public function logout(#[CurrentUser] User $user): JsonResponse
    {
        $this->authService->logout($user);

        return response()->json(status: 204);
    }
}
