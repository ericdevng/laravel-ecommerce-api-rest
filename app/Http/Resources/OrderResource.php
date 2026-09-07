<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'total' => $this->total,
            'status' => $this->status->value,
            'created_at' => $this->created_at,
            'items' => $this->whenLoaded('items', fn () => OrderItemResource::collection($this->items)),
        ];
    }
}
