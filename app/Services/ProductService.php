<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    public function __construct(private readonly CategoryService $categoryService) {}

    public function index(
        ?int $categoryId = null,
        int $page = 1,
        ?string $search = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        ?string $sortBy = null,
        ?string $sortDir = null,
    ): LengthAwarePaginator {
        $key = 'products.index.'.md5(serialize([ // Se serializan los parámetros para generar una clave única.
            $categoryId, $page, $search, $minPrice, $maxPrice, $sortBy, $sortDir,
        ]));

        return Cache::remember($key, 60, function () use ( // Se cachea por 60 segundos para mejorar el rendimiento.
            $categoryId, $page, $search, $minPrice, $maxPrice, $sortBy, $sortDir,
        ): LengthAwarePaginator {
            $query = Product::query()->active()->with('category');

            // Filtro por categoría (existente).
            if ($categoryId !== null) {
                $this->categoryService->show($categoryId);
                $query->where('category_id', $categoryId);
            }

            // busqueda por nombre o descripcion, se usa lower para que funcione en postgre porque es case sensitive, y se usa like con % para que busque en cualquier parte del texto
            if ($search !== null && $search !== '') {
                $searchValue = '%'.strtolower($search).'%';
                $query->where(function ($q) use ($searchValue) {
                    $q->whereRaw('LOWER(name) LIKE ?', [$searchValue])
                      ->orWhereRaw('LOWER(description) LIKE ?', [$searchValue]);
                });
            }

            // por rango de precio
            if ($minPrice !== null) {
                $query->where('price', '>=', $minPrice);
            }
            if ($maxPrice !== null) {
                $query->where('price', '<=', $maxPrice);
            }

            // Ordenamiento: por defecto, mas recientes primero. Se permite ordenar por precio, nombre o fecha de creación, en ascendente o descendente
            $allowedSorts = ['price', 'name', 'created_at'];
            $allowedDirs = ['asc', 'desc'];

            if ($sortBy !== null && in_array($sortBy, $allowedSorts, true)) {
                $direction = ($sortDir !== null && in_array($sortDir, $allowedDirs, true))
                    ? $sortDir
                    : 'asc';
                $query->orderBy($sortBy, $direction);
            } else {
                //mas recientes primero
                $query->latest();
            }

            return $query->paginate(15, ['*'], 'page', $page); // paginacion de 15 productos por pagina, se puede cambiar a otro valor si se desea
        });
    }

    public function show(int $id): Product
    {
        return Cache::remember("products.show.{$id}", 60, function () use ($id): Product {
            $product = Product::query()->active()->with('category')->find($id);

            if (! $product) {
                throw new BusinessException('Product not found.', 404);
            }

            return $product;
        });
    }

    //se invalida la cahce para que la proxima vez que se consulte el producto se vuelva a generar la cache con los datos actualizados
    public static function invalidateCache(int $productId): void
    {
        Cache::forget("products.show.{$productId}");
    }
}
