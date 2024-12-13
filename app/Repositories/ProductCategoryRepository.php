<?php 
namespace App\Repositories;

use App\Models\ProductCategory;
use Illuminate\Support\Facades\Auth;

class ProductCategoryRepository
{
    public function all()
    {
        return ProductCategory::where('company_id',Auth::user()->company_id)->paginate(15)
        ->through(function ($category) { 
            return [
                'uuid' => $category->uuid,
                'name' => $category->name,
                'slug' => $category->slug,
                'status' => $category->status,  
            ];
        });

    }

    public function find($id)
    {
       return  ProductCategory::with('company:id,name')
        ->where('uuid', $id)
        ->first(); 
    }

    public function create(array $data)
    {
        return ProductCategory::create($data);
    }

    public function update(ProductCategory $category, array $data)
    {
        return $category->update($data);
    }

    public function delete(ProductCategory $category)
    {
        return $category->delete();
    }
}
