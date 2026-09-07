<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService){}

    public function index(Request $request): AnonymousResourceCollection {
        $categoryId = $request->has('category') ? (int) $request->query('category') : null; // Si se da una categoria, se filtra por esa mismqa; de lo contrario, se muestran todos los productos.
        $page = (int) $request->query('page', 1); // Se obtiene el numero de paginade la consulta, por defecto es 1 si no se proporciona
        $search = $request->query('search'); 
        $minPrice = $request->has('min_price') ? (float) $request->query('min_price') : null; // Si se da un precio minimo, se filtra por ese; de lo contrario, no se filtra por precio minimo
        $maxPrice = $request->has('max_price') ? (float) $request->query('max_price') : null; // lo mismo para el precio maximo
        $sortBy = $request->query('sort_by'); // Se obtiene el parametro de ordenamiento, si se proporciona
        $sortDir = $request->query('sort_dir'); // Se obtiene la direccion de ordenamiento, si se proporciona

        return ProductResource::collection($this->productService->index( // Se llama al servicio de productos para obtener la lista de productos filtrados y ordenados según los parametros proporcionados
            $categoryId, $page, $search, $minPrice, $maxPrice, $sortBy, $sortDir,
        ));
    }

    public function show(int $id): JsonResponse {
        return response()->json(new ProductResource($this->productService->show($id)));
    }
}
