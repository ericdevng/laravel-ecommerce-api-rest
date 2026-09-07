<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService,) {} // Inyección de dependencias del servicio de categorías

    public function index(Request $request): AnonymousResourceCollection {
        $page = (int) $request->query('page', 1);
        return CategoryResource::collection($this->categoryService->index($page));
    }

    public function show(int $id): JsonResponse {
        return response()->json(new CategoryResource($this->categoryService->show($id)));
    }
}
