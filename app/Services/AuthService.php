<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public function register(array $data): User {
        return User::create($data);
    }

    public function login(array $credentials): string {
        if (!$token = auth('api')->attempt($credentials)) {
            Log::warning('Failed login attempt', [
                'email' => $credentials['email'] ?? 'unknown',
            ]);
            throw new BusinessException('Invalid credentials.', 401);
        }
        return $token;
    }

    public function issueToken(User $user): string {
        return auth('api')->login($user);
    }

    public function logout(): void {
        auth('api')->logout();
    }
}
