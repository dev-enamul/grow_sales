<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\CategoryType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryTypeController extends Controller
{
    public function index(Request $request)
    { 
        $keyword = $request->keyword;
        $selectOnly = $request->boolean('select');   
        $query = CategoryType::where('company_id', Auth::user()->company_id) 
            ->when($keyword, function ($query) use ($keyword) {
                $query->where('name', 'like', '%' . $keyword . '%');
            });

        if ($selectOnly) {
            $units = $query->select('uuid', 'name')->latest()->take(10)->get();
            return success_response($units);
        }

        $categoryType = $query
            ->select('uuid', 'name', 'is_active')
            ->paginate(10);

        return success_response($categoryType);
    }


    public function store(Request $request){ 
        $request->validate([
            'name' => 'required|string|max:255', 
        ]);  

        $categoryType = new CategoryType();
        $categoryType->name = $request->input('name');   
        $categoryType->save();  
        return success_response(null, 'Category type created successfully!',201); 
    }  

    public function update(Request $request, $uuid)
    { 
        $request->validate([
            'name' => 'required|string|max:255', 
        ]);
 
        $categoryType = CategoryType::findByUuid($uuid);  
        if (!$categoryType) {
            return error_response("Category type not found", 404);
        }  
        $categoryType->name = $request->input('name'); 
        $categoryType->is_active = $request->input('is_active', $categoryType->is_active);   
        $categoryType->save(); 
        return success_response(null, 'Category Type updated successfully!');
    }

    public function destroy($id)
    {
        $area = CategoryType::findByUuid($id);
        $area->delete(); 
        return success_response(null,'Category type deleted successfully.'); 
    }
}
