<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Services\ProductService;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function show(int $id)
    {
        $product = $this->productService->getProductStockInfo($id);

        if (!$product) {
            return ApiResponse::error('Product not found', [], 404);
        }

        return ApiResponse::success('Product retrieved', $product);
    }
}
