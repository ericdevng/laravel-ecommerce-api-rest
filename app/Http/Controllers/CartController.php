<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartItemResource;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService,) {} // Inyección de dependencias del servicio de carrito

    public function index(): JsonResponse {
        return response()->json(new CartResource($this->cartService->getContent(auth('api')->user())));
    }

    public function store(AddToCartRequest $request): JsonResponse {
        $item = $this->cartService->addItem(
            auth('api')->user(),
            (int) $request->validated('product_id'),
            (int) $request->validated('quantity'),
        );

        return response()->json(new CartItemResource($item), 201);
    }

    public function destroy(int $item): JsonResponse {
        $user = auth('api')->user();
        $cartItem = $this->findCartItem($user, $item);
        $this->cartService->removeItem($user, $cartItem);

        return response()->json([
            'message' => 'Item eliminado del carrito.',
        ]);
    }

    public function update(UpdateCartItemRequest $request, int $item): JsonResponse {
        $user = auth('api')->user();
        $cartItem = $this->findCartItem($user, $item);
        $updatedItem = $this->cartService->updateItem(
            $user,
            $cartItem,
            (int) $request->validated('quantity'),
        );

        return response()->json(new CartItemResource($updatedItem));
    }

    // Busca un item en el carrito del usuario autenticado y 
    //devuelve el objeto CartItem correspondiente. Si no se encuentra, lanza un 404.

    private function findCartItem($user, int $itemId): CartItem {
        $cart = $this->cartService->getCart($user);

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('id', $itemId)
            ->first();

        if (! $cartItem) {
            abort(404, 'Cart item not found.');
        }

        return $cartItem;
    }
}
