<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct( private readonly AuthService $authService,) {} // Inyeccion de dependencias: autenticacion

    public function register(RegisterRequest $request): JsonResponse { // Los requisitos de validacion
        $user = $this->authService->register($request->validated());
        $token = $this->authService->issueToken($user); // Se emite un token para el usuario registrado, con el mismo token se puede hacer login y logout del recien registrado

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201); // 201 Created la respuesta está lista para mandar al service de auth
    }

    public function login(LoginRequest $request): JsonResponse { // Requisitos para login
        $token = $this->authService->login($request->validated());

        return response()->json([ // Se pasa a response como respuesta un json con el token emitido
            'token' => $token,
        ]);
    }

    public function logout(): JsonResponse { // Logout de usuario solo se rompe/invalida el token
        $this->authService->logout(); // se destruye la sesion 
        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
