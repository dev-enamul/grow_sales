<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\ProductUnit;
use App\Traits\PaginatorTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PropertyUnitController extends Controller
{
    use PaginatorTrait;
    public function index(Request $request)
    {
        $keyword = $request->keyword;
        $selectOnly = $request->boolean('select');
        $query = ProductUnit::where('company_id', Auth::user()->company_id)
            ->where('applies_to','property')
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%')
                      ->orWhere('abbreviation', 'like', '%' . $keyword . '%');
                });
            });

        // For select dropdowns
        if ($selectOnly) {
            $units = $query->select('id', 'name')->latest()->take(10)->get();
            return success_response($units);
        }

        // Use the trait for pagination
        $query = $query->select('id', 'uuid', 'name', 'abbreviation', 'is_active');
        $productUnit = $this->paginateQuery($query, $request); 
        return success_response($productUnit);
    }


    public function store(Request $request){ 
        $request->validate([
            'name' => 'required|string|max:255',
            'abbreviation' => 'nullable|string|max:10', 
        ]);
        $productUnit = new ProductUnit();
        $productUnit->name = $request->input('name');
        $productUnit->abbreviation = $request->input('abbreviation'); 
        $productUnit->applies_to = 'property';  
        $productUnit->save();  
        return success_response($productUnit, 'Product unit created successfully!',201); 
    } 

    public function show($unit_uuid){
        $product_unit = ProductUnit::where('uuid',$unit_uuid)
        ->where('company_id',Auth::user()->company_id)
        ->select('uuid','name', 'abbreviation', 'is_active')
        ->first();  
        if(!$product_unit){
            error_response("Product unit not found",404);
        } 
        return success_response($product_unit);
    } 


    public function update(Request $request, $unit_uuid)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'abbreviation' => 'nullable|string|max:10', 
        ]);
 
        $productUnit = ProductUnit::where('uuid', $unit_uuid)
            ->where('company_id', Auth::user()->company_id)
            ->first();  
        if (!$productUnit) {
            return error_response("Product unit not found", 404);
        }  
        $productUnit->name = $request->input('name');
        $productUnit->abbreviation = $request->input('abbreviation');
        $productUnit->is_active = $request->input('is_active', $productUnit->is_active);   
        $productUnit->save(); 
        return success_response(null, 'Product unit updated successfully!');
    }

    public function destroy($id)
    {
        $area = ProductUnit::findByUuid($id);
        $area->delete(); 
        return success_response(null,'Product unit deleted successfully.'); 
    }

}
