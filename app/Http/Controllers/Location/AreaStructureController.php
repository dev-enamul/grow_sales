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
    public function index(Request $request)
    {
        $query = AreaStructure::with('parent:id,name')
                    ->select('id', 'uuid', 'name', 'parent_id', 'status');

        if ($request->boolean('select')) {
             $datas = $query->where('status', 1)->latest()->get()->map(function($item) {
                 return [
                     'id' => $item->id,
                     'text' => $item->name,
                     'name' => $item->name,
                     'uuid' => $item->uuid,
                 ];
             });
             return success_response($datas);
        }

        $datas = $query->latest()
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id'         => $item->id,
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
            'parent_id' => 'nullable|exists:area_structures,id',
            'status' => 'required|in:0,1',
        ]);

        AreaStructure::create([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
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
            'parent_id' => 'nullable|exists:area_structures,id',
            'status' => 'in:0,1',
        ]);

        $parentId = $request->parent_id;
        if ($parentId) {
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
