<?php

namespace App\Services;

use App\DTO\LoginDTO;
use App\DTO\RegisterDTO;
use App\Http\Resources\AuthResource;
use App\Models\User;
use App\Exceptions\InvalidCredentialsException;
use Illuminate\Support\Facades\Hash;

final class AuthService
{
    public function register(RegisterDTO $dto): AuthResource
    {
        $user = User::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $dto->password,
        ]);

        $token = $user->createToken('auth')->plainTextToken;

        return AuthResource::make($user)->additional(['token' => $token]);
    }

    public function login(LoginDTO $dto): AuthResource
    {
        $user = User::where('email', $dto->email)->first();

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        $token = $user->createToken('auth')->plainTextToken;

        return AuthResource::make($user)->additional(['token' => $token]);
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
