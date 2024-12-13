<?php 
namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductCategoryRequest;
use App\Services\ProductCategoryService; 

class ProductCategoryController extends Controller
{
    protected $productCategoryService;

    public function __construct(ProductCategoryService $productCategoryService)
    {
        $this->productCategoryService = $productCategoryService;
    }

    public function index()
    {
        $categories = $this->productCategoryService->index();
        return success_response($categories);
    }

    public function show($id)
    {
        try {
            $category = $this->productCategoryService->show($id);
            return success_response($category);
        } catch (\Exception $e) {
            return error_response($e->getMessage(), $e->getCode());
        }
    }

    public function store(ProductCategoryRequest $request)
    {
        try {
            $data = $request->validated();
            $this->productCategoryService->store($data);
            return success_response(null, 'Product category created successfully.');
        } catch (\Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function update(ProductCategoryRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $this->productCategoryService->update($id, $data);
            return success_response(null, 'Product category updated successfully.');
        } catch (\Exception $e) {
            return error_response($e->getMessage(), $e->getCode());
        }
    }

    public function destroy($id)
    {
        try {
            $this->productCategoryService->destroy($id);
            return success_response(null, 'Product category deleted successfully.');
        } catch (\Exception $e) {
            return error_response($e->getMessage(),$e->getCode());
        }
    }
}
