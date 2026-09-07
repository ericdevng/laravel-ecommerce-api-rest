<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool {
        return true;
    }

    public function rules(): array { // requisitos para actualizar un item del carrito
        return [
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
