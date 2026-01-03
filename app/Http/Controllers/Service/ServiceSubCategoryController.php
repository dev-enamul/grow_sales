<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use App\Traits\PaginatorTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceSubCategoryController extends Controller
{
    use PaginatorTrait; 
    
    public function index(Request $request)
    {
        $keyword = $request->keyword; 
        $selectOnly = $request->boolean('select');
        $categoryId = $request->category_id; // Filter by category
        
        $query = ProductSubCategory::where('company_id', Auth::user()->company_id)
            ->where('applies_to','service')
            ->when($categoryId, function ($query) use ($categoryId) {
                // Find category by UUID if provided
                $category = ProductCategory::where('id', $categoryId)
                    ->where('company_id', Auth::user()->company_id)
                    ->first();
                if ($category) {
                    $query->where('category_id', $category->id);
                }
            })
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('description', 'like', '%' . $keyword . '%');
                });
            }) 
            ->select('id','uuid', 'name','slug', 'code', 'description', 'category_id', 'image', 'status')
            ->with(['category:id,uuid,name,description,image']);
        
        if ($selectOnly) {
            $subCategories = $query->select('id','name')->latest()->take(10)->get();
            return success_response($subCategories);
        }

        // Sorting
        $sortBy = $request->input('sort_by');
        $sortOrder = strtolower($request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSorts = ['name'];

        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        $paginated = $this->paginateQuery($query, $request);
 
        $paginated['data'] = collect($paginated['data'])->map(function ($item) {
            return [ 
                'id' => $item->uuid,
                'name' => $item->name,  
                'slug' => $item->slug, 
                'status' => $item->status,
                'description' => $item->description, 
                'category' => $item->category ? [
                    'id' => $item->category->id,
                    'name' => $item->category->name,
                    'description' => $item->category->description,
                    'image' => $item->category->image,
                    'image_url' => getFileUrl($item->category->image),
                ] : null,
                'category_name' => $item->category ? $item->category->name : null, // Keep for backward compat if needed, or remove if unused. Used in column filter text? The column filter uses `category_name` dataIndex for filter matching but I am using custom render.
                'category_id' => $item->category ? $item->category->id : null,
                'image' => $item->image,
                'image_url' => getFileUrl($item->image),
            ];
        });
        return success_response($paginated);
    }

    public function show($uuid)
    {
        $subCategory = ProductSubCategory::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->where('applies_to', 'service')
            ->with([
                'category:id,uuid,name',
                'creator:id,name',
                'updater:id,name',
                'deleter:id,name'
            ])
            ->first();

        if (!$subCategory) {
            return error_response('Service sub-category not found', 404);
        }

        return success_response([ 
            'id' => $subCategory->uuid,
            'name' => $subCategory->name,
            'slug' => $subCategory->slug, 
            'description' => $subCategory->description,    
            'status' => $subCategory->status,
            'category_name' => $subCategory->category ? $subCategory->category->name : null,
            'category_id' => $subCategory->category_id,
            'image' => $subCategory->image,
            'image_url' => getFileUrl($subCategory->image),
            'created_by' => optional($subCategory->creator)->name,
            'updated_by' => optional($subCategory->updater)->name,
            'deleted_by' => optional($subCategory->deleter)->name,
            'created_at' => formatDate($subCategory->created_at),
            'updated_at' => formatDate($subCategory->updated_at),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string', 
            'category_id' => 'required|exists:product_categories,id',
            'image' => 'nullable|exists:files,id',
            'status' => 'nullable|in:0,1',
        ]);

        

        $subCategory = new ProductSubCategory(); 
        $subCategory->name = $request->name;
        $subCategory->slug = getSlug($subCategory, $request->name);
        $subCategory->description = $request->description;
        $subCategory->category_id = $request->category_id; 
        $subCategory->image = $request->image;
        $subCategory->status = $request->status ?? 1;
        $subCategory->applies_to = 'service'; 
        $subCategory->save();

        return success_response(null, 'Service sub-category created successfully!', 201);
    }

    public function update(Request $request, $uuid)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string', 
            'category_id' => 'nullable|exists:product_categories,id',
            'image' => 'nullable|exists:files,id',
            'status' => 'nullable|in:0,1',
        ]);

        $subCategory = ProductSubCategory::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->where('applies_to', 'service')
            ->first();

        if (!$subCategory) {
            return error_response('Service sub-category not found', 404);
        }

        // If category_id is being updated, verify it belongs to service
        

        $subCategory->category_id = $request->category_id; 

        $subCategory->name = $request->name;
        $subCategory->slug = getSlug(new ProductSubCategory(), $request->name);
        $subCategory->code = $request->code;
        $subCategory->description = $request->description;
        if ($request->has('status')) {
            $subCategory->status = $request->status;
        }
        if ($request->has('image')) {
            $subCategory->image = $request->image;
        }
        $subCategory->updated_by = Auth::id();
        $subCategory->save();

        return success_response(null, 'Service sub-category updated successfully!');
    }

    public function destroy($uuid)
    {
        $subCategory = ProductSubCategory::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->where('applies_to', 'service')
            ->first();

        if (!$subCategory) {
            return error_response('Service sub-category not found', 404);
        }
 
        $productCount = Product::where('sub_category_id', $subCategory->id)->count();
        if ($productCount > 0) {
            return error_response("You can't delete this sub-category because it has {$productCount} products.", 400);
        }

        $subCategory->deleted_by = Auth::id();
        $subCategory->save();
        $subCategory->delete();

        return success_response(null, 'Service sub-category deleted successfully.');
    }
}

