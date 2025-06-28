<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\MeasurmentUnit;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MeasurmentUnitController extends Controller
{
    public function index(Request $request)
    { 
        $keyword = $request->keyword;
        $selectOnly = $request->boolean('select');
        $query = MeasurmentUnit::where('company_id', Auth::user()->company_id) 
            ->when($keyword, function ($query) use ($keyword) {
                $query->where('name', 'like', '%' . $keyword . '%')
                ->orWhere('abbreviation', 'like', '%' . $keyword . '%');
            });

        if ($selectOnly) {
            $units = $query->select('id', 'name')->latest()->take(10)->get();
            return success_response($units);
        }

        $sortBy = $request->input('sort_by');
        $sortOrder = $request->input('sort_order', 'asc');

        $allowedSorts = ['name','abbreviation']; 
        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();  
        }

        $measurmentUnit = $query
            ->select('uuid', 'name', 'abbreviation', 'is_active')
            ->paginate(10);

        return success_response($measurmentUnit);
    }


    public function store(Request $request){ 
        $request->validate([
            'name' => 'required|string|max:255',
            'abbreviation' => 'nullable|string|max:10', 
        ]);  

        $measurmentUnit = new MeasurmentUnit();
        $measurmentUnit->name = $request->input('name');
        $measurmentUnit->abbreviation = $request->input('abbreviation');   
        $measurmentUnit->save();  
        return success_response(null, 'Measurment unit created successfully!',201); 
    }  

    public function update(Request $request, $unit_uuid)
    { 
        $request->validate([
            'name' => 'required|string|max:255',
            'abbreviation' => 'nullable|string|max:10', 
        ]);
 
        $measurmentUnit = MeasurmentUnit::where('uuid', $unit_uuid)
            ->where('company_id', Auth::user()->company_id)
            ->first();  
        if (!$measurmentUnit) {
            return error_response("Product unit not found", 404);
        }  
        $measurmentUnit->name = $request->input('name');
        $measurmentUnit->abbreviation = $request->input('abbreviation');
        $measurmentUnit->is_active = $request->input('is_active', $measurmentUnit->is_active);   
        $measurmentUnit->save(); 
        return success_response(null, 'Measurment unit updated successfully!');
    }

    public function destroy($id)
    {
        $area = MeasurmentUnit::findByUuid($id);
        $area->delete(); 
        return success_response(null,'Measurment unit deleted successfully.'); 
    }
}
