<?php

namespace App\Http\Controllers\Property;

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

class UnitController extends Controller
{
    use PaginatorTrait; 
    public function index(Request $request)
    {
        $selectOnly = $request->boolean('select');
        $keyword = $request->keyword;

        $query = Product::where('company_id', Auth::user()->company_id)
            ->where('applies_to','property')
            ->with([
                'category:id,name',
                'subCategory:id,name',
                'productUnit:id,name',
                'measurmentUnit:id,name',
                'vatSetting:id,name,vat_percentage'
            ]);

        // Keyword Search
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('slug', 'like', "%{$keyword}%")
                ->orWhere('code', 'like', "%{$keyword}%");
            });
        }

        // Filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('sub_category_id')) {
            $query->where('sub_category_id', $request->sub_category_id);
        }

        if ($request->filled('product_unit_id')) {
            $query->where('product_unit_id', $request->product_unit_id);
        }

        if ($request->filled('measurment_unit_id')) {
            $query->where('measurment_unit_id', $request->measurment_unit_id);
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

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        

        // Dropdown select mode
        if ($selectOnly) {
            $list = $query->select('id', 'name', 'price','sell_price')->latest()->limit(10)->get()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sell_price' => $item->sell_price,
                ];
            });
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

        // Format data
        $paginated['data'] = $paginated['data']->map(function ($item) {
            return [
                'id'               => $item->id,
                'uuid'             => $item->uuid,
                'name'             => $item->name,
                'slug'             => $item->slug,
                'code'             => $item->code,
                'description'      => $item->description,
                'rate'             => $item->rate,
                'quantity'         => $item->quantity,
                'total_price'      => $item->price,
                'other_price'      => $item->other_price ?? 0,
                'discount'         => $item->discount ?? 0,
                'vat_amount'       => $item->vat_amount ?? 0,
                'sell_price'       => $item->sell_price ?? 0,
                'unit_price'       => $item->rate,
                'qty_in_stock'     => $item->qty_in_stock,
                'floor'            => $item->floor,
                'status'           => $item->status,
                'category'         => optional($item->category)->name,
                'sub_category'     => optional($item->subCategory)->name,
                'measurment_unit'  => optional($item->measurmentUnit)->name,
                'vat'              => (@$item->vatSetting->vat_percentage ?? 0) . '%',
                'vat_setting_id'   => $item->vat_setting_id,
                'category_id'      => $item->category_id,
                'sub_category_id'  => $item->sub_category_id,
                'product_unit_id'  => $item->product_unit_id,
                'unit_name'        => optional($item->productUnit)->name,
            ];
        });  
        return success_response($paginated);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255', 
            'description' => 'nullable|string',
            'code' => 'nullable|string|max:100',
            'rate' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'other_price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'product_unit_id' => 'nullable|exists:product_units,id',
            'measurment_unit_id' => 'nullable|exists:measurment_units,id',
            'category_id' => 'required|exists:product_categories,id',
            'sub_category_id' => 'nullable|exists:product_sub_categories,id',
            'vat_setting_id' => 'nullable|exists:vat_settings,id',
            'vat_amount' => 'nullable|numeric|min:0',
            'sell_price' => 'nullable|numeric|min:0',
            'qty_in_stock' => 'nullable|integer',
            'floor' => 'required|integer',
        ]);

        $product = new Product(); 
        $product->name = $request->name;
        $product->slug = getSlug(new Product(),$request->name);
        $product->description = $request->description;
        $product->code = $request->code;
        $product->rate = $request->rate;
        $product->quantity = $request->quantity;
        $product->price = $request->price;
        $product->other_price = $request->other_price ?? 0;
        $product->discount = $request->discount ?? 0;
        $product->product_unit_id = $request->product_unit_id;
        $product->measurment_unit_id = $request->measurment_unit_id;
        $product->category_id = $request->category_id;
        $product->sub_category_id = $request->sub_category_id;
        $product->vat_setting_id = $request->vat_setting_id;
        $product->vat_amount = $request->vat_amount ?? 0;
        $product->sell_price = $request->sell_price ?? 0;
        $product->qty_in_stock = $request->qty_in_stock??0;
        $product->floor = $request->floor;
        $product->status = 0; 
        $product->applies_to = 'property';
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
            'description' => 'nullable|string',
            'code' => 'nullable|string|max:100',
            'rate' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'other_price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'product_unit_id' => 'nullable|exists:product_units,id',
            'measurment_unit_id' => 'nullable|exists:measurment_units,id',
            'category_id' => 'required|exists:product_categories,id',
            'sub_category_id' => 'nullable|exists:product_sub_categories,id',
            'vat_setting_id' => 'nullable|exists:vat_settings,id',
            'vat_amount' => 'nullable|numeric|min:0',
            'sell_price' => 'nullable|numeric|min:0',
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
            'name', 'description', 'code', 'rate', 'quantity', 'price', 'other_price', 'discount',
            'product_unit_id', 'measurment_unit_id', 'category_id', 'sub_category_id', 'vat_setting_id', 
            'vat_amount', 'sell_price', 'qty_in_stock', 'floor'
        ])); 
        $product->slug  = getSlug(new Product(),$request->name);
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
