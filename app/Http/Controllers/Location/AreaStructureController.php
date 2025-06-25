<?php

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AreaStructure;
use App\Traits\ValidatesParent;
use Illuminate\Http\Request;

class AreaStructureController extends Controller
{
    use ValidatesParent;
    public function index()
    {
        $datas = AreaStructure::with('parent:id,name')  
                    ->select('id', 'uuid', 'name', 'parent_id', 'status')  
                    ->latest()
                    ->get()
                    ->map(function ($item) {
                        return [
                            'uuid'       => $item->uuid,
                            'name'       => $item->name,
                            'parent_id'  => $item->parent_id,
                            'parent_name'=> $item->parent ? $item->parent->name : null,
                            'status'     => $item->status,
                        ];
                    });

        return success_response($datas);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'parent_id' => 'nullable|exists:area_structures,uuid',
            'status' => 'required|in:0,1',
        ]);

        $parentId = null;
        if ($request->filled('parent_id')) {
            $parentId = AreaStructure::findByUuid($request->parent_id)->id; // ✅ UUID → id
        }

        AreaStructure::create([
            'name' => $request->name,
            'parent_id' => $parentId, // ✅ correct integer id
            'status' => $request->status,
            'created_by' => auth()->id(),
            'company_id' => auth()->user()->company_id,
        ]);

        return success_response(null, 'Structure created successfully');
    }


    public function update(Request $request, $id)
    { 
        $structure = AreaStructure::findByUuid($id); 
        $request->validate([
            'name' => 'required|string',
            'parent_id' => 'nullable|exists:area_structures,uuid',
            'status' => 'in:0,1',
        ]); 
        $parentId = null;
        if ($request->filled('parent_id')) { 
            $parent = AreaStructure::findByUuid($request->parent_id);
            if (!$parent) {
                return error_response(null, 422, 'Parent not found');
            }
            $parentId = $parent->id; 
            if (!$this->isValidParent($structure, $parentId)) {
                return error_response(null, 422, 'Invalid parent: circular reference detected.');
            }
        } 
        $structure->update([
            'name' => $request->name,
            'parent_id' => $parentId,
            'status' => $request->status ?? $structure->status,
        ]);

        return success_response(null, 'Structure updated successfully');
    }


     public function destroy($id)
    {
        $structure = AreaStructure::findByUuid($id); 
        $hasArea = Area::where('area_structure_id', $structure->id)->exists(); 
        if ($hasArea) {
            return error_response(null, 422, 'Cannot delete structure because it has '.$structure->name);
        } 
        AreaStructure::where('parent_id', $structure->id)->update(['parent_id' => null]); 
        $structure->delete(); 
        return success_response(null, 'Structure deleted successfully');
    }

}
