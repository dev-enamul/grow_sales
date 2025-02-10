<?php 
namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductCategoryRepository;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\User;
use App\Models\VatSetting;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProductService
{
    protected $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function index($request)
    {
        return $this->productRepository->all($request);
    }

    public function show($id)
    {
        $product = $this->productRepository->find($id); 
        if (!$product) {
            throw new \Exception("Product category not found", 404);
        } 
        return [
            'uuid' => $product->uuid,
            'name' => $product->name,
            'slug' => $product->slug,
            'regular_price' => $product->regular_price,
            'sell_price' => $product->sell_price,
            'status' => $product->status, 
        ];
    }

    public function store($data)
    { 
        return $data;
        $product_unit = ProductUnit::where('uuid', $data->product_unit_id)->first();
        $vat_setting = VatSetting::where('uuid', $data->vat_setting_id)->first();
        $product_category = ProductCategory::where('uuid', $data->category_id)->first();
        
        $user = Auth::user();
        $model = new Product();
        $data['company_id'] = $user->company_id; 
        $data['product_unit_id'] = $product_unit->id;
        $data['vat_setting_id'] = $vat_setting->id;
        $data['category_id'] = $product_category->id;
        $data['created_by'] = $user->id;
        $data['updated_by'] = null;
        $data['deleted_by'] = null;
        $data['slug'] = getSlug($model,$data['name']); 
        return $this->productRepository->create($data);
    }

    public function update($id, $data)
    {
        $model = new Product();
        $product = $this->productRepository->find($id);  
        if (!$product) {
            throw new \Exception("Product not found", 404);
        } 
        $data['updated_by'] = 1; 
        $data['slug'] = getSlug($product,$data['name']);

        return $this->productRepository->update($model, $data);
    }

    public function destroy($id)
    {
        $category = $this->productRepository->find($id); 
        if (!$category) {
            throw new \Exception("Product not found", 404);
        } 
        return $this->productRepository->delete($category);
    } 
}
