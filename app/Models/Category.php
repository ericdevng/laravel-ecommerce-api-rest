<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'name',
        'is_active',
    ];


    protected function casts(): array{// la categoria esta activo? true o false
        return [
            'is_active' => 'boolean',
        ];
    }

    public function products(): HasMany{ // una categoria tiene muchos productos
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder{ // scope para filtrar las categorias activas
        return $query->where('is_active', true);
    }
}
