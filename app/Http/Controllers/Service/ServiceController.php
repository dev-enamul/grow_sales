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
        $minPrice = $request->min_price; // Price range filter - minimum
        $maxPrice = $request->max_price; // Price range filter - maximum
        $minRate = $request->min_rate; // Rate filter - minimum
        $maxRate = $request->max_rate; // Rate filter - maximum
        
        $query = Product::where('company_id', Auth::user()->company_id)
            ->where('applies_to','service')
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($subCategoryId, function ($query) use ($subCategoryId) {
                $query->where('sub_category_id', $subCategoryId);
            })
            ->when($minPrice !== null, function ($query) use ($minPrice) {
                $query->where('price', '>=', $minPrice);
            })
            ->when($maxPrice !== null, function ($query) use ($maxPrice) {
                $query->where('price', '<=', $maxPrice);
            })
            ->when($minRate !== null, function ($query) use ($minRate) {
                $query->where('rate', '>=', $minRate);
            })
            ->when($maxRate !== null, function ($query) use ($maxRate) {
                $query->where('rate', '<=', $maxRate);
            })
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('slug', 'like', '%' . $keyword . '%')
                        ->orWhere('code', 'like', '%' . $keyword . '%');
                });
            }) 
            ->select('id','uuid', 'name','slug', 'code', 'description', 'rate', 'quantity', 'price', 'measurment_unit_id', 'other_price', 'discount', 'vat_setting_id', 'vat_rate', 'vat_amount', 'sell_price', 'category_id', 'sub_category_id', 'image')
            ->with(['category:id,uuid,name', 'subCategory:id,uuid,name', 'vatSetting:id,vat_percentage', 'measurmentUnit:id,name']);
        
        if ($selectOnly) {
            $services = $query->select('id','name', 'price')->latest()->take(10)->get()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sell_price' => $item->price,
                ];
            });
            return success_response($services);
        }

        $sortBy = $request->input('sort_by');
        $sortOrder = $request->input('sort_order', 'asc');

        $allowedSorts = ['name', 'code', 'price', 'rate']; 
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
                'measurment_unit_id' => $item->measurment_unit_id,
                'other_price' => $item->other_price,
                'discount' => $item->discount,
                'vat_id' => $item->vat_setting_id,
                'vat_setting_id' => $item->vat_setting_id,
                'vat_rate' => $item->vat_rate,
                'vat_amount' => $item->vat_amount,
                'sell_price' => $item->sell_price,
                'vat_percentage' => $item->vatSetting ? $item->vatSetting->vat_percentage : null,
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
                'measurmentUnit:id,name',
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
            'measurment_unit_id' => $service->measurment_unit_id,
            'other_price' => $service->other_price,
            'discount' => $service->discount,
            'vat_id' => $service->vat_setting_id,
            'vat_setting_id' => $service->vat_setting_id,
            'vat_rate' => $service->vat_rate,
            'vat_amount' => $service->vat_amount,
            'sell_price' => $service->sell_price,
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
            'vat_setting_id' => 'nullable|exists:vat_settings,id',
            'vat_id' => 'nullable|exists:vat_settings,id', // Backward compatibility
            'category_id' => 'nullable|exists:product_categories,id',
            'sub_category_id' => 'nullable|exists:product_sub_categories,id',
            'image' => 'nullable|exists:files,id',
        ]);

        // Verify category and sub-category belong to service and same company (only if provided)
        if ($request->category_id) {
            $category = ProductCategory::where('id', $request->category_id)
                ->where('company_id', Auth::user()->company_id)
                ->where('applies_to', 'service')
                ->first();

            if (!$category) {
                return error_response('Service category not found or invalid', 404);
            }
        }

        // Verify sub-category belongs to the category and same company (only if provided)
        if ($request->sub_category_id) {
            $subCategory = ProductSubCategory::where('id', $request->sub_category_id)
                ->where('company_id', Auth::user()->company_id)
                ->where('applies_to', 'service')
                ->when($request->category_id, function ($query) use ($request) {
                    return $query->where('category_id', $request->category_id);
                })
                ->first();

            if (!$subCategory) {
                return error_response('Service sub-category not found or invalid', 404);
            }
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
        $service->measurment_unit_id = $request->measurment_unit_id;
        $service->other_price = $request->other_price ?? 0;
        $service->discount = $request->discount ?? 0;
        $service->vat_setting_id = $request->vat_setting_id ?? $request->vat_id;
        $service->vat_rate = $request->vat_rate;
        $service->vat_amount = $request->vat_amount;
        $service->sell_price = $request->sell_price;
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
            'measurment_unit_id' => 'nullable|exists:measurment_units,id',
            'other_price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'vat_setting_id' => 'nullable|exists:vat_settings,id',
            'vat_id' => 'nullable|exists:vat_settings,id', // Backward compatibility
            'vat_rate' => 'nullable|numeric|min:0',
            'vat_amount' => 'nullable|numeric|min:0',
            'sell_price' => 'nullable|numeric|min:0',
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
        if ($request->has('category_id') && $request->category_id) {
            $category = ProductCategory::where('id', $request->category_id)
                ->where('company_id', Auth::user()->company_id)
                ->where('applies_to', 'service')
                ->first();

            if (!$category) {
                return error_response('Service category not found or invalid', 404);
            }
        }

        // If sub_category_id is being updated, verify it belongs to service and category
        if ($request->has('sub_category_id') && $request->sub_category_id) {
            $subCategory = ProductSubCategory::where('id', $request->sub_category_id)
                ->where('company_id', Auth::user()->company_id)
                ->where('applies_to', 'service')
                ->when($request->category_id, function ($query) use ($request) {
                    return $query->where('category_id', $request->category_id);
                })
                ->first();

            if (!$subCategory) {
                return error_response('Service sub-category not found or invalid', 404);
            }
        }

        $service->name = $request->name;
        $service->slug = getSlug(new Product(), $request->name);
        $service->code = $request->code;
        $service->description = $request->description;
        $service->rate = $request->rate ?? $service->rate;
        $service->quantity = $request->quantity ?? $service->quantity;
        $service->price = $request->price ?? $service->price;
        
        if ($request->has('measurment_unit_id')) {
            $service->measurment_unit_id = $request->measurment_unit_id;
        }
        if ($request->has('other_price')) {
            $service->other_price = $request->other_price ?? 0;
        }
        if ($request->has('discount')) {
            $service->discount = $request->discount ?? 0;
        }
        if ($request->has('vat_setting_id') || $request->has('vat_id')) {
            $service->vat_setting_id = $request->vat_setting_id ?? $request->vat_id;
        }
        if ($request->has('vat_rate')) {
            $service->vat_rate = $request->vat_rate;
        }
        if ($request->has('vat_amount')) {
            $service->vat_amount = $request->vat_amount;
        }
        if ($request->has('sell_price')) {
            $service->sell_price = $request->sell_price;
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

