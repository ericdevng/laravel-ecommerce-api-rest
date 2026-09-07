<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function getCart(User $user): Cart { // Obtiene el carrito del usuario, si no existe, lo crea
        return Cart::firstOrCreate(['user_id' => $user->id]);
    }

    public function getContent(User $user): Cart{ // Obtener el contenido del carrito por medio del usuario, si no existe, lo crea y carga los items y productos relacionados
        return $this->getCart($user)->load('items.product'); // load('items.product') evita el problema de N+1 queries al cargar los productos o las consultas repetidas
    }

    public function addItem(User $user, int $productId, int $quantity): CartItem { // agregar al carrito; necesitamos un usuario autenticado, un id de producto y una cantidad
        //Retornamos una transaccion, esto para que en caso de errores no se agreguen cosas sin querer al carrito
        return DB::transaction(function () use ($user, $productId, $quantity): CartItem {
            $product = Product::query()->lockForUpdate()->active()->find($productId); // query para buscar el producto, se almacena en variable

            if (!$product) { // Si no esta o es falso
                throw new BusinessException('Product not found.', 404);
            }

            $cart = $this->getCart($user); // tomamos el carrito del usuario y lo almacenamos en $cart

            $item = $cart->items()->firstOrNew(['product_id' => $productId]); // esta variable tiene los items ordenados del carrito
            $newQuantity = $item->exists ? $item->quantity + $quantity : $quantity; // algoritmo para los nuevos totales de items en el carrito (individualmente)

            if ($newQuantity > $product->stock) { //si la nueva cantidad es mayor que el stock disponible
                throw new BusinessException('Insufficient stock.', 422);
            }

            $item->quantity = $newQuantity; // new quantity ya tiene el total de items
            $item->unit_price = $product->price; // y su precio
            $item->save(); // guardamos con eloquent

            return $item->load('product'); //devolvemos los nuevos productos
        });
    }

    public function removeItem(User $user, CartItem $item): void { // Eliminar del carrito
        $cart = $this->getCart($user); // obtenemos el carrito del usuaruo

        if ($item->cart_id !== $cart->id) {  // si el id del item no esta/no es igual al del cartid
            throw new BusinessException('Product not found in cart.', 404);
        }

        $item->delete();
    }

    public function updateItem(User $user, CartItem $item, int $quantity): CartItem {
        return DB::transaction(function () use ($user, $item, $quantity): CartItem {
            $cart = $this->getCart($user);

            if ($item->cart_id !== $cart->id) {
                throw new BusinessException('Product not found in cart.', 404);
            }

            if ($quantity <= 0) {
                throw new BusinessException('Quantity must be greater than zero.', 422);
            }

            // lockForUpdate previene race condition: dos requests concurrentes
            // con quantity=100 no pueden pasar ambas la validación de stock.
            $product = Product::query()->lockForUpdate()->find($item->product_id);

            if ($quantity > $product->stock) {
                throw new BusinessException('Insufficient stock.', 422);
            }

            $item->quantity = $quantity;
            $item->save();

            return $item->load('product');
        });
    }
}
