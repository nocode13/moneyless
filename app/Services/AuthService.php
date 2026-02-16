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
     * @return array{User,string}
     */
    public function register(RegisterDTO $dto): array
    {
        $user = User::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $dto->password,
        ]);

        $token = $this->getToken($user);

        return [$user, $token];
    }

    /**
     * @return array{User,string}
     */
    public function login(LoginDTO $dto): array
    {
        $user = User::where('email', $dto->email)->first();

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        $token = $this->getToken($user);

        return [$user, $token];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    private function getToken(User $user): string
    {
        return $user->createToken('auth')->plainTextToken;
    }
}
