<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use App\Traits\PaginatorTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    use PaginatorTrait; 
    
    public function index(Request $request)
    {
        $keyword = $request->keyword; 
        $selectOnly = $request->boolean('select');
        $categoryId = $request->category_id; // Filter by category ID
        $subCategoryId = $request->sub_category_id; // Filter by sub-category ID
        
        $query = Product::where('company_id', Auth::user()->company_id)
            ->where('applies_to','service')
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($subCategoryId, function ($query) use ($subCategoryId) {
                $query->where('sub_category_id', $subCategoryId);
            })
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('slug', 'like', '%' . $keyword . '%')
                        ->orWhere('code', 'like', '%' . $keyword . '%');
                });
            }) 
            ->select('id','uuid', 'name','slug', 'code', 'description', 'rate', 'quantity', 'price', 'category_id', 'sub_category_id', 'vat_setting_id', 'image')
            ->with(['category:id,uuid,name', 'subCategory:id,uuid,name']);
        
        if ($selectOnly) {
            $services = $query->select('id','name')->latest()->take(10)->get();
            return success_response($services);
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
                'code' => $item->code,
                'description' => $item->description,
                'rate' => $item->rate,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'vat_id' => $item->vat_setting_id,
                'category_id' => $item->category ? $item->category->id : null,
                'category_name' => $item->category ? $item->category->name : null,
                'sub_category_id' => $item->subCategory ? $item->subCategory->id : null,
                'sub_category_name' => $item->subCategory ? $item->subCategory->name : null,
                'image' => $item->image,
                'image_url' => getFileUrl($item->image),
            ];
        });
        return success_response($paginated);
    }

    public function show($uuid)
    {
        $service = Product::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->where('applies_to', 'service')
            ->with([
                'category:id,uuid,name',
                'subCategory:id,uuid,name',
                'vatSetting:id,name',
                'creator:id,name',
                'updater:id,name',
                'deleter:id,name'
            ])
            ->first();

        if (!$service) {
            return error_response('Service not found', 404);
        }

        return success_response([ 
            'id' => $service->uuid,
            'name' => $service->name,
            'slug' => $service->slug,
            'code' => $service->code,
            'description' => $service->description,   
            'rate' => $service->rate,
            'quantity' => $service->quantity,
            'price' => $service->price,
            'vat_id' => $service->vat_setting_id,
            'category_id' => $service->category ? $service->category->id : null,
            'category_name' => $service->category ? $service->category->name : null,
            'sub_category_id' => $service->subCategory ? $service->subCategory->id : null,
            'sub_category_name' => $service->subCategory ? $service->subCategory->name : null,
            'image' => $service->image,
            'image_url' => getFileUrl($service->image),
            'created_by' => optional($service->creator)->name,
            'updated_by' => optional($service->updater)->name,
            'deleted_by' => optional($service->deleter)->name,
            'created_at' => formatDate($service->created_at),
            'updated_at' => formatDate($service->updated_at),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'code' => 'nullable|string|max:100',
            'rate' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'vat_id' => 'nullable|exists:vat_settings,id',
            'category_id' => 'required|exists:product_categories,id',
            'sub_category_id' => 'required|exists:product_sub_categories,id',
            'image' => 'nullable|exists:files,id',
        ]);

        // Verify category and sub-category belong to service and same company
        $category = ProductCategory::where('id', $request->category_id)
            ->where('company_id', Auth::user()->company_id)
            ->where('applies_to', 'service')
            ->first();

        if (!$category) {
            return error_response('Service category not found or invalid', 404);
        }
 

        $service = new Product();
        $service->company_id = Auth::user()->company_id;
        $service->name = $request->name;
        $service->slug = getSlug($service, $request->name);
        $service->code = $request->code;
        $service->description = $request->description;
        $service->rate = $request->rate ?? 0;
        $service->quantity = $request->quantity ?? 0;
        $service->price = $request->price ?? 0;
        $service->vat_setting_id = $request->vat_id;
        $service->category_id = $request->category_id;
        $service->sub_category_id = $request->sub_category_id;
        $service->image = $request->image;
        $service->applies_to = 'service';
        $service->status = 0;
        $service->created_by = Auth::id();
        $service->save();

        return success_response(null, 'Service created successfully!', 201);
    }

    public function update(Request $request, $uuid)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'code' => 'nullable|string|max:100',
            'rate' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'vat_id' => 'nullable|exists:vat_settings,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'sub_category_id' => 'nullable|exists:product_sub_categories,id',
            'image' => 'nullable|exists:files,id',
        ]);

        $service = Product::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->where('applies_to', 'service')
            ->first();

        if (!$service) {
            return error_response('Service not found', 404);
        }

        // If category_id is being updated, verify it belongs to service
        if ($request->has('category_id')) {
            $category = ProductCategory::where('id', $request->category_id)
                ->where('company_id', Auth::user()->company_id)
                ->where('applies_to', 'service')
                ->first();

            if (!$category) {
                return error_response('Service category not found or invalid', 404);
            }
        }

       

        $service->name = $request->name;
        $service->slug = getSlug(new Product(), $request->name);
        $service->code = $request->code;
        $service->description = $request->description;
        $service->rate = $request->rate ?? $service->rate;
        $service->quantity = $request->quantity ?? $service->quantity;
        $service->price = $request->price ?? $service->price;
        
        if ($request->has('vat_id')) {
            $service->vat_setting_id = $request->vat_id;
        }
        if ($request->has('category_id')) {
            $service->category_id = $request->category_id;
        }
        if ($request->has('sub_category_id')) {
            $service->sub_category_id = $request->sub_category_id;
        }
        if ($request->has('image')) {
            $service->image = $request->image;
        }
        
        $service->updated_by = Auth::id();
        $service->save();

        return success_response(null, 'Service updated successfully!');
    }

    public function destroy($uuid)
    {
        $service = Product::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->where('applies_to', 'service')
            ->first();

        if (!$service) {
            return error_response('Service not found', 404);
        }

        $service->deleted_by = Auth::id();
        $service->save();
        $service->delete();

        return success_response(null, 'Service deleted successfully.');
    }
}

