<?php

namespace App\Services;

use App\DTO\LoginDTO;
use App\DTO\RegisterDTO;
use App\Models\User;
use App\Exceptions\InvalidCredentialsException;
use Illuminate\Support\Facades\Hash;

final class AuthService
{
    /**
     * @return array{user: User, token: string}
     */
    public function register(RegisterDTO $dto): array
    {
        $user = User::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $dto->password,
        ]);

        $token = $user->createToken('auth')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    /**
     * @return array{user: User, token: string}
     */
    public function login(LoginDTO $dto): array
    {
        $user = User::where('email', $dto->email)->first();

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        $token = $user->createToken('auth')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
