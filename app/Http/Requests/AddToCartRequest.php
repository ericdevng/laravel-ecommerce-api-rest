<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Request para agregar al carrito

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool { // De Laravel para validar si el usuario esta autorizado a hacer la peticion, en este caso es true porque cualquier usuario autenticado puede agregar un item al carrito
        return true;
    }

    public function rules(): array { // Reglas de validacion
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'], // Sin embargo estos son los requisitos minimos para agregar un producto al carrito
        ];
    }
}
