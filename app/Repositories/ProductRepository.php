<?php 
namespace App\Repositories;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class ProductRepository
{
    public function all()
    {
        return Product::where('company_id',Auth::user()->company_id)
        ->select('uuid', 'name', 'slug', 'regular_price', 'sell_price', 'status','category_id')
        ->with('category:id,name')
        ->paginate(15)
        ->through(function ($product) {
            return [
                'uuid' => $product->uuid,
                'name' => $product->name,
                'slug' => $product->slug,
                'regular_price' => $product->regular_price,
                'sell_price' => $product->sell_price,
                'status' => $product->status, 
                'category' => $product->category ? $product->category->name : null,
            ];
        }); 
    }

    public function find($id)
    {
        return Product::with('company:id,name')
        ->where('uuid', $id)
        ->first();   
 
    }

    public function create(array $data)
    {
        return Product::create($data);
    }

    public function update($product, array $data)
    { 
        return $product->update($data);
    }

    public function delete(Product $category)
    {
        return $category->delete();
    }
}
