<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model {
    protected $table = 'cart';
    protected $fillable = [
        'user_id',
    ];

    public function user(): BelongsTo{ // un carrito pertenece a un usuario
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany{ // un carrito tiene muchos items
        return $this->hasMany(CartItem::class);
    }
}
