<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductRequest;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\VatSetting;
use App\Services\ProductService;
use App\Traits\PaginatorTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    use PaginatorTrait;

    public function index(Request $request)
    {
        $query = Product::where('company_id', Auth::user()->company_id)
            ->with(['category:id,name', 'subCategory:id,name', 'productUnit:id,name', 'vatSetting:id,name']);

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
                'name' => $item->name,
                'slug' => $item->slug,
                'code' => $item->code,
                'unit_price' => $item->unit_price,
                'unit' => $item->unit,
                'total_price' => $item->total_price,
                'qty_in_stock' => $item->qty_in_stock,
                'status' => $item->status,
                'category_name' => optional($item->category)->name,
                'sub_category_name' => optional($item->subCategory)->name,
                'unit_name' => optional($item->productUnit)->name,
                'vat_name' => optional($item->vatSetting)->name,
            ];
        });

        return success_response($data);
    } 

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug,NULL,id,company_id,' . Auth::user()->company_id,
            'description' => 'nullable|string',
            'code' => 'nullable|string|max:100',
            'unit_price' => 'required|numeric|min:0',
            'unit' => 'required|integer|min:1',
            'product_unit_id' => 'nullable|exists:product_units,id',
            'category_id' => 'required|exists:product_categories,id',
            'sub_category_id' => 'nullable|exists:product_sub_categories,id',
            'vat_setting_id' => 'nullable|exists:vat_settings,id',
            'qty_in_stock' => 'nullable|integer',
            'floor' => 'nullable|integer',
        ]);

        $product = new Product();
        $product->company_id = Auth::user()->company_id;
        $product->name = $request->name;
        $product->slug = $request->slug;
        $product->description = $request->description;
        $product->code = $request->code;
        $product->unit_price = $request->unit_price;
        $product->unit = $request->unit;
        $product->total_price = $request->unit_price * $request->unit;
        $product->product_unit_id = $request->product_unit_id;
        $product->category_id = $request->category_id;
        $product->sub_category_id = $request->sub_category_id;
        $product->vat_setting_id = $request->vat_setting_id;
        $product->qty_in_stock = $request->qty_in_stock;
        $product->floor = $request->floor;
        $product->status = 1;
        $product->created_by = Auth::id();
        $product->save();

        return success_response(null, 'Product created successfully!', 201);
    }

    public function show($uuid)
    {
        $product = Product::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->with(['category', 'subCategory', 'productUnit', 'vatSetting'])
            ->first();

        if (!$product) {
            return error_response('Product not found', 404);
        }

        return success_response($product);
    }

    public function update(Request $request, $uuid)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'description' => 'nullable|string',
            'code' => 'nullable|string|max:100',
            'unit_price' => 'required|numeric|min:0',
            'unit' => 'required|integer|min:1',
            'product_unit_id' => 'nullable|exists:product_units,id',
            'category_id' => 'required|exists:product_categories,id',
            'sub_category_id' => 'nullable|exists:product_sub_categories,id',
            'vat_setting_id' => 'nullable|exists:vat_settings,id',
            'qty_in_stock' => 'nullable|integer',
            'floor' => 'nullable|integer',
            'status' => 'nullable|in:0,1',
        ]);

        $product = Product::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->first();

        if (!$product) {
            return error_response('Product not found', 404);
        }

        $product->fill($request->only([
            'name', 'slug', 'description', 'code', 'unit_price', 'unit', 'product_unit_id',
            'category_id', 'sub_category_id', 'vat_setting_id', 'qty_in_stock', 'floor', 'status'
        ]));

        $product->total_price = $request->unit_price * $request->unit;
        $product->updated_by = Auth::id();
        $product->save();

        return success_response(null, 'Product updated successfully!');
    }

    public function destroy($uuid)
    {
        $product = Product::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->first();

        if (!$product) {
            return error_response('Product not found', 404);
        }

        $product->deleted_by = Auth::id();
        $product->save();
        $product->delete();

        return success_response(null, 'Product deleted successfully.');
    }


   
}
