<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    public function index(int $page = 1): LengthAwarePaginator {// devuelve las categorias activas, active() contiene paginacion OJO 
        return Cache::remember("categories.index.page.{$page}", 60, function () use ($page): LengthAwarePaginator {
            return Category::query()
                ->active()
                ->paginate(15, ['*'], 'page', $page);
        });
    }

    public function show(int $id): Category { //muestra una categoria por medio del id de la misma categoria
        return Cache::remember("categories.show.{$id}", 60, function () use ($id): Category { //usamos cache para evitar consultas repetidas
            $category = Category::query()
                ->active()
                ->find($id);

            if (!$category) {
                throw new BusinessException('Category not found.', 404);
            }

            return $category;
        });
    }
}
