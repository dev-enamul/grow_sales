<?php 
namespace App\Services;

use App\Repositories\ProductCategoryRepository;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProductCategoryService
{
    protected $productCategoryRepository;

    public function __construct(ProductCategoryRepository $productCategoryRepository)
    {
        $this->productCategoryRepository = $productCategoryRepository;
    }

    public function index()
    {
        return $this->productCategoryRepository->all();
    }

    public function show($id)
    {
        $category = $this->productCategoryRepository->find($id);

        if (!$category) {
            throw new \Exception("Product category not found", 404);
        }

        return $category;
    }

    public function store($data)
    {
        $user = Auth::user();
        $model = new ProductCategory();

        $data['uuid'] = Str::uuid();
        $data['company_id'] = $user->company_id;
        $data['created_by'] = $user->id;
        $data['updated_by'] = null;
        $data['deleted_by'] = null;
        $data['slug'] = getSlug($model, $data['name']); 
        $data['status'] = $data['status'] ?? 1;  

        $data['category_type_id'] = $data['category_type_id'] ?? null;
        $data['measurment_unit_id'] = $data['measurment_unit_id'] ?? null;
        $data['area_id'] = $data['area_id'] ?? null;
        $data['description'] = $data['description'] ?? null;
        $data['delivery_date'] = $data['delivery_date'] ?? null;
        $data['address'] = $data['address'] ?? null;
        $data['latitude'] = $data['latitude'] ?? null;
        $data['longitude'] = $data['longitude'] ?? null; 
        return $this->productCategoryRepository->create($data);
    }

    public function update($id, $data)
    {
        $category = $this->productCategoryRepository->find($id); 
        $model = new ProductCategory();
        if (!$category) {
            throw new \Exception("Product category not found", 404);
        } 
        $data['updated_by'] = 1; 
        $data['slug'] = getSlug($model,$data['name']);

        return $this->productCategoryRepository->update($category, $data);
    }

    public function destroy($id)
    {
        $category = $this->productCategoryRepository->find($id);

        if (!$category) {
            throw new \Exception("Product category not found", 404);
        }

        return $this->productCategoryRepository->delete($category);
    } 
}
