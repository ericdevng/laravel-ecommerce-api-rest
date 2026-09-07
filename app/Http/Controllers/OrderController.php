<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService){} // Inyección de dependencias del servicio de pedidos

    public function store(): JsonResponse {
        $order = $this->orderService->checkout(auth('api')->user());
        return response()->json(new OrderResource($order), 201);
    }

    public function index(): AnonymousResourceCollection {
        return OrderResource::collection($this->orderService->index(auth('api')->user()));
    }

    public function show(int $id): JsonResponse {
        return response()->json(new OrderResource($this->orderService->show(auth('api')->user(), $id)));
    }
}
