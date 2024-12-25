<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductRequest;
use App\Models\Company;
use App\Models\ProductUnit;
use App\Models\VatSetting;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $productService; 
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        $categories = $this->productService->index($request);
        return success_response($categories);
    }

    public function show($id)
    {
        try {
            $category = $this->productService->show($id);
            return success_response($category);
        } catch (\Exception $e) {
            return error_response($e->getMessage(), $e->getCode());
        }
    }

    public function store(ProductRequest $request)
    {
        try {
            $data = $request->all();
            $this->productService->store($data);
            return success_response(null, 'Product created successfully.');
        } catch (\Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function update(ProductRequest $request, $id)
    {
        try {
            $data = $request->all();
            $this->productService->update($id, $data);
            return success_response(null, 'Product updated successfully.');
        } catch (\Exception $e) {
            return error_response($e->getMessage(), $e->getCode());
        }
    }

    public function destroy($id)
    {
        try {
            $this->productService->destroy($id);
            return success_response(null, 'Product deleted successfully.');
        } catch (\Exception $e) {
            return error_response($e->getMessage(),$e->getCode());
        }
    } 

   
}
