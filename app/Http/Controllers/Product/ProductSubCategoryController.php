<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\ProductSubCategory;
use App\Traits\PaginatorTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductSubCategoryController extends Controller
{
    use PaginatorTrait;

    public function index(Request $request)
    {
        $selectOnly = $request->boolean('select');
        $keyword = $request->keyword;

        $query = ProductSubCategory::where('company_id', Auth::user()->company_id)
            ->with(['category:id,name,measurment_unit_id', 'productUnit:id,name', 'vatSetting:id,name,vat_percentage']);

        

        // Apply filters
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('slug', 'like', "%{$keyword}%")
                ->orWhere('code', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('product_unit_id')) {
            $query->where('product_unit_id', $request->product_unit_id);
        }

        if ($request->filled('vat_setting_id')) {
            $query->where('vat_setting_id', $request->vat_setting_id);
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->filled('min_quantity')) {
            $query->where('quantity', '>=', $request->min_quantity);
        }

        if ($request->filled('max_quantity')) {
            $query->where('quantity', '<=', $request->max_quantity);
        }

         // Filters tracker
        $filters = [
            'keyword'         => $keyword,
            'category_id'     => $request->category_id,
            'product_unit_id' => $request->product_unit_id,
            'vat_setting_id'  => $request->vat_setting_id,
            'min_price'       => $request->min_price,
            'max_price'       => $request->max_price,
            'min_quantity'    => $request->min_quantity,
            'max_quantity'    => $request->max_quantity,
        ];

        foreach ($filters as $field => $operator) {
            if ($value = $request->get($field)) {
                $query->where($field, $operator, $operator === 'like' ? "%$value%" : $value);
            }
        }


        // Dropdown option
        if ($selectOnly) {
            $list = $query->select('id', 'name')->latest()->limit(10)->get();
            return success_response($list);
        }

        // Sorting
        $sortBy = $request->input('sort_by');
        $sortOrder = $request->input('sort_order', 'asc');
        $allowedSorts = ['name', 'rate', 'quantity', 'price'];

        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        // Pagination
        $paginated = $this->paginateQuery($query, $request);

        // Map data
        $paginated['data'] = $paginated['data']->map(function ($item) {
            return [
                'id'           => $item->id,
                'uuid'         => $item->uuid,
                'name'         => $item->name, 
                'code'         => $item->code,
                'description'  => $item->description, 
                'rate'         => $item->rate, 
                'quantity'     => $item->quantity,
                'measurment_unit' => $item?->category?->measurmentUnit?->name??"",
                'total_price'  => $item->price,
                'category'     => optional($item->category)->name,
                'vat'          => (@$item->vatSetting->vat_percentage ?? 0) . "%" ,
                'vat_setting_id' => $item->vat_setting_id,
                'category_id'  => $item->category_id,
                'unit_name'    => optional($item->productUnit)->name, 
                'product_unit_id'  => $item->product_unit_id,
            ];
        });

        // Attach filter data
        $paginated['filters'] = $filters;

        return success_response($paginated);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name'              => 'required|string|max:255', 
            'code'              => 'nullable|string|max:100',
            'description'       => 'nullable|string',
            'rate'              => 'required|numeric|min:0',
            'quantity'          => 'required|integer|min:1',
            'price'             => 'required|numeric|min:0',
            'product_unit_id'   => 'nullable|exists:product_units,id',
            'category_id'       => 'nullable|exists:product_categories,id',
            'vat_setting_id'    => 'nullable|exists:vat_settings,id',
        ]);

        $sub = new ProductSubCategory([ 
            'name'             => $request->name,
            'slug'             => getSlug(new ProductSubCategory(),$request->name),
            'code'             => $request->code,
            'description'      => $request->description,
            'rate'       => $request->rate,
            'quantity'         => $request->quantity,
            'price'      => $request->price,
            'product_unit_id'  => $request->product_unit_id,
            'category_id'      => $request->category_id,
            'vat_setting_id'   => $request->vat_setting_id, 
        ]);

        $sub->save();

        return success_response(null, 'Sub-category created successfully!', 201);
    } 

    public function update(Request $request, $uuid)
    {
        $request->validate([
            'name'             => 'required|string|max:255', 
            'code'             => 'nullable|string|max:100',
            'description'      => 'nullable|string',
            'rate'              => 'required|numeric|min:0',
            'quantity'         => 'required|integer|min:1',
            'price'             => 'required|numeric|min:1',
            'product_unit_id'  => 'nullable|exists:product_units,id',
            'category_id'      => 'nullable|exists:product_categories,id',
            'vat_setting_id'   => 'nullable|exists:vat_settings,id',
        ]);

        $sub = ProductSubCategory::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->first();

        if (!$sub) {
            return error_response('Sub-category not found', 404);
        } 
        $sub->fill([
            'name'             => $request->name,
            'slug'             => getSlug(new ProductSubCategory(), $request->name),
            'code'             => $request->code,
            'description'      => $request->description,
            'rate'       => $request->rate,
            'quantity'         => $request->quantity,
            'price'      => $request->price,
            'product_unit_id'  => $request->product_unit_id,
            'category_id'      => $request->category_id,
            'vat_setting_id'   => $request->vat_setting_id, 
        ]);

        $sub->save();

        return success_response(null, 'Sub-category updated successfully!');
    }

    public function destroy($uuid)
    {
        $sub = ProductSubCategory::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->first(); 
        if (!$sub) {
            return error_response('Sub-category not found', 404);
        }
        $sub->save();
        $sub->delete();

        return success_response(null, 'Sub-category deleted successfully.');
    }
}
