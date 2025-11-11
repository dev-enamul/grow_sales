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
                        ->orWhere('slug', 'like', '%' . $keyword . '%')
                        ->orWhere('address', 'like', '%' . $keyword . '%');
                });
            }) 
            ->select('id','uuid', 'name','slug', 'description', 'status')
            ->with(['area:id,name']);
        
        if ($selectOnly) {
            $units = $query->select('id','name')->latest()->take(10)->get();
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
            'created_by' => optional($category->creator)->name,
            'updated_by' => optional($category->updater)->name,
            'deleted_by' => optional($category->deleter)->name,
            'created_at' => $category->created_at,
            'updated_at' => $category->updated_at,
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category = new ProductCategory();
        $category->company_id = Auth::user()->company_id;
        $category->name = $request->name;
        $category->slug = getSlug($category,$request->name);
        $category->description = $request->description;
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
            return error_response('Service category not found', 404);
        }
 
        $subCategoryCount = ProductSubCategory::where('category_id', $category->id)->count();
        if ($subCategoryCount > 0) {
            return error_response("You can't delete this category because it has {$subCategoryCount} sub-categories.", 400);
        }
 
        $productCount = Product::where('category_id', $category->id)->count();
        if ($productCount > 0) {
            return error_response("You can't delete this category because it has {$productCount} products.", 400);
        }

        $category->deleted_by = Auth::id();
        $category->save();
        $category->delete();

        return success_response(null, 'Service category deleted successfully.');
    }

}
