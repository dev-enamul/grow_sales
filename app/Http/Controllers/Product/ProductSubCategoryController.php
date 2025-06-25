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
        $query = ProductSubCategory::where('company_id', Auth::user()->company_id)
            ->with(['category:id,name', 'productUnit:id,name', 'vatSetting:id,name']);

        if ($keyword = $request->keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('slug', 'like', "%{$keyword}%")
                ->orWhere('code', 'like', "%{$keyword}%");
            });
        }

        $data = $this->paginateQuery($query, $request);

        $data['data'] = collect($data['data'])->map(function ($item) {
            return [
                'id' => $item->id,
                'uuid' => $item->uuid,
                'name' => $item->name, 
                'code' => $item->code,
                // 'unit_price' => $item->unit_price,
                // 'unit' => $item->unit,
                'price' => $item->total_price,
                'category' => optional($item->category)->name,
                'unit' => optional($item->productUnit)->name,
                'vat' => optional($item->vatSetting)->name,
            ];
        });

        return success_response($data);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:product_sub_categories,slug,NULL,id,company_id,' . Auth::user()->company_id,
            'code' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'unit_price' => 'required|numeric|min:0',
            'unit' => 'required|integer|min:1',
            'product_unit_id' => 'nullable|exists:product_units,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'vat_setting_id' => 'nullable|exists:vat_settings,id',
        ]);

        $sub = new ProductSubCategory();
        $sub->company_id = Auth::user()->company_id;
        $sub->name = $request->name;
        $sub->slug = $request->slug;
        $sub->code = $request->code;
        $sub->description = $request->description;
        $sub->unit_price = $request->unit_price;
        $sub->unit = $request->unit;
        $sub->total_price = $request->unit_price * $request->unit;
        $sub->product_unit_id = $request->product_unit_id;
        $sub->category_id = $request->category_id;
        $sub->vat_setting_id = $request->vat_setting_id;
        $sub->created_by = Auth::id();
        $sub->save();

        return success_response(null, 'Sub category created successfully!', 201);
    } 

    public function show($uuid)
    {
        $sub = ProductSubCategory::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->with(['category:id,name', 'productUnit:id,name', 'vatSetting:id,name'])
            ->first();

        if (!$sub) {
            return error_response('Sub category not found', 404);
        }

        return success_response([
            'id' => $sub->id,
            'name' => $sub->name,
            'slug' => $sub->slug,
            'code' => $sub->code,
            'description' => $sub->description,
            'unit_price' => $sub->unit_price,
            'unit' => $sub->unit,
            'total_price' => $sub->total_price,
            'category_name' => optional($sub->category)->name,
            'unit_name' => optional($sub->productUnit)->name,
            'vat_name' => optional($sub->vatSetting)->name,
        ]);
    }

    public function update(Request $request, $uuid)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'code' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'unit_price' => 'required|numeric|min:0',
            'unit' => 'required|integer|min:1',
            'product_unit_id' => 'nullable|exists:product_units,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'vat_setting_id' => 'nullable|exists:vat_settings,id',
        ]);

        $sub = ProductSubCategory::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->first();

        if (!$sub) {
            return error_response('Sub category not found', 404);
        }

        $sub->name = $request->name;
        $sub->slug = $request->slug;
        $sub->code = $request->code;
        $sub->description = $request->description;
        $sub->unit_price = $request->unit_price;
        $sub->unit = $request->unit;
        $sub->total_price = $request->unit_price * $request->unit;
        $sub->product_unit_id = $request->product_unit_id;
        $sub->category_id = $request->category_id;
        $sub->vat_setting_id = $request->vat_setting_id;
        $sub->updated_by = Auth::id();
        $sub->save();

        return success_response(null, 'Sub category updated successfully!');
    }

    public function destroy($uuid)
    {
        $sub = ProductSubCategory::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->first();

        if (!$sub) {
            return error_response('Sub category not found', 404);
        }

        $sub->deleted_by = Auth::id();
        $sub->save();
        $sub->delete();

        return success_response(null, 'Sub category deleted successfully.');
    } 

}
