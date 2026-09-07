<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\BusinessException;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use App\Services\ProductService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    // convertir el carrito en una orden, vaciando el carrito y creando la orden con sus items
    public function checkout(User $user): Order {
        $cart = Cart::with('items.product') // el carrito no debe estar vacio
            ->where('user_id', $user->id)
            ->first();

        if (!$cart || $cart->items->isEmpty()) { // si el carrito no tiene nada o no existe, error
            throw new BusinessException('Cart is empty.', 422);
        }

        return DB::transaction(function () use ($cart): Order { //transaccion 
            $cart = Cart::lockForUpdate()->find($cart->id); // el bloqueo evita que otro checkout consuma el stock mientras verificamos y creamos la orden

            // Verificamos de nuevo después del lock: entre la primera lectura y este punto. Prevencion de doble clic
            if ($cart->items()->count() === 0) {
                throw new BusinessException('Cart is empty.', 422);
            }

            // Se suma en centavos para evitar problemas de precision con float double
            $totalCents = 0;
            $orderItems = [];

            foreach ($cart->items as $item) {
                // bloqueamos el producto para evitar que otro checkout consuma el stock mientras verificamos y creamos la orden
                $product = Product::query()->lockForUpdate()->find($item->product_id);

                if ($product->stock <= 0) {
                    Log::warning('Checkout failed: product out of stock', [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'user_id' => $cart->user_id,
                    ]);
                    throw new BusinessException(
                        'Out of stock for product "'.$product->name.'".',
                        422
                    );
                }

                if ($product->stock < $item->quantity) { 
                    Log::warning('Checkout failed: insufficient stock', [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'requested' => $item->quantity,
                        'available' => $product->stock,
                        'user_id' => $cart->user_id,
                    ]);
                    throw new BusinessException(
                        'Insufficient stock for product "'.$product->name.'".',
                        422
                    );
                }

                // Restamos del stock la cantidad comprada.
                $product->decrement('stock', $item->quantity);

                // se incalida la cache porque el stock cambio y la cache de productos tiene el stock congelado
                ProductService::invalidateCache($product->id);

                // actualiza el total de la orden en centavos y prepara los items para crear la orden
                $totalCents += $item->quantity * Money::toCents($item->unit_price);

                $orderItems[] = [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                ];
            }

            // Creamos la orden en estado 'pending' o no pagada
            $order = Order::create([
                'user_id' => $cart->user_id,
                'total' => Money::fromCents($totalCents),
                'status' => OrderStatus::Pending,
            ]);

            // Insertamos los items de la orden en una sola operacion
            $order->items()->createMany($orderItems);

            // Vaciamos el carrito: se eliminan todos sus items
            $cart->items()->delete();

            Log::info('Order created successfully', [
                'order_id' => $order->id,
                'user_id' => $cart->user_id,
                'total' => $order->total,
                'items_count' => count($orderItems),
            ]);

            // Recargamos las relaciones para devolver la orden completa
            // con sus items y productos en la respuesta JSON.
            return $order->load('items.product');
        });
    }

    //lista de ordenes
    public function index(User $user): LengthAwarePaginator
    {
        return Order::query()
            ->with('items.product')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate();
    }

    // Muestra la orden del usuario por id, incluyendo sus items y productos.
    // Si la orden no pertenece al usuario o no existe, lanza BusinessException
    public function show(User $user, int $id): Order
    {
        $order = Order::query()
            ->with('items.product')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $order) {
            throw new BusinessException('Order not found.', 404);
        }

        return $order;
    }
}
