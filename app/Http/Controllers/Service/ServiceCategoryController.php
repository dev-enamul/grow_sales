<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use App\Traits\PaginatorTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceCategoryController extends Controller
{
    use PaginatorTrait; 
    public function index(Request $request)
    {
        $keyword = $request->keyword; 
        $selectOnly = $request->boolean('select');
        $query = ProductCategory::where('company_id', Auth::user()->company_id)
            ->where('applies_to','service')
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('description', 'like', '%' . $keyword . '%');
                });
            }) 
            ->select('id','uuid', 'name','slug', 'description', 'status', 'image')
            ->with(['area:id,name']);
        
        if ($selectOnly) {
            $units = $query->latest()->take(10)->get();
            $units = $units->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                ];
            });
            return success_response($units);
        }

        $sortBy = $request->input('sort_by');
        $sortOrder = $request->input('sort_order', 'asc');

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
                'image' => $item->image,
                'image_url' => getFileUrl($item->image),
            ];
        });
        return success_response($paginated);
    }

    public function show($uuid)
    {
        $category = ProductCategory::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->with([
                'creator:id,name',
                'updater:id,name',
                'deleter:id,name'
            ])
            ->first();

        if (!$category) {
            return error_response('Product category not found', 404);
        }

        return success_response([ 
            'id' => $category->uuid,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,   
            'status' => $category->status,
            'image' => $category->image,
            'image_url' => getFileUrl($category->image),
            'created_by' => optional($category->creator)->name,
            'updated_by' => optional($category->updater)->name,
            'deleted_by' => optional($category->deleter)->name,
            'created_at' => formatDate($category->created_at),
            'updated_at' => formatDate($category->updated_at),
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|exists:files,id',
        ]);

        $category = new ProductCategory();
        $category->company_id = Auth::user()->company_id;
        $category->name = $request->name;
        $category->slug = getSlug($category,$request->name);
        $category->description = $request->description;
        $category->image = $request->image;
        $category->progress_stage = 'Ready';  
        $category->status = 1;
        $category->applies_to = 'service';  
        $category->created_by = Auth::id();
        $category->save();

        return success_response(null, 'Product category created successfully!', 201);
    }


    public function update(Request $request, $uuid)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:0,1',
            'image' => 'nullable|exists:files,id',
        ]);

        $category = ProductCategory::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->where('applies_to', 'service')
            ->first();

        if (!$category) {
            return error_response('Service category not found', 404);
        }

        $category->name = $request->name;
        $category->slug = getSlug(new ProductCategory(), $request->name);
        $category->description = $request->description;
        $category->status = $request->status ?? $category->status;
        if ($request->has('image')) {
            $category->image = $request->image;
        }
        $category->updated_by = Auth::id();
        $category->save();

        return success_response(null, 'Service category updated successfully!');
    }


    public function destroy($uuid)
    {
        $category = ProductCategory::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->where('applies_to', 'service')
            ->first();

        if (!$category) {
            return error_response(null, 404,'Service category not found');
        }
 
        $subCategoryCount = ProductSubCategory::where('category_id', $category->id)->count();
        if ($subCategoryCount > 0) {
            return error_response(null, 400,"You can't delete this category because it has {$subCategoryCount} sub-categories.");
        }
 
        $productCount = Product::where('category_id', $category->id)->count();
        if ($productCount > 0) {
            return error_response(null, 400,"You can't delete this category because it has {$productCount} products.");
        }

        $category->deleted_by = Auth::id();
        $category->save();
        $category->delete();

        return success_response(null, 'Service category deleted successfully.');
    }

}
