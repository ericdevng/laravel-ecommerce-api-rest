<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array { // transformamos el recurso a un array para que pueda ser devuelto como json
        return [
            'id' => $this->id,
            'items' => $this->whenLoaded('items', fn () => CartItemResource::collection($this->items)),
        ];
    }
}
