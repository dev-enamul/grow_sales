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
            'parent_id' => 'nullable|exists:area_structures,id',
            'status' => 'in:0,1'
        ]);

        AreaStructure::create([ 
            'name' => $request->name,
            'parent_id' => $request->parent_id??null,
            'status'   => $request->status,
        ]);
        return success_response(null,'Structure created successfully'); 
    }

    public function update(Request $request, $id)
    {
        $structure = AreaStructure::findByUuid($id);
        $request->validate([
            'name' => 'required|string', 
            'parent_id' => 'nullable|exists:area_structures,id',
            'status' => 'in:0,1',
        ]); 
        if ($request->filled('parent_id') && !$this->isValidParent($structure, $request->parent_id)) {
            return error_response(null,422,'Invalid parent: circular reference detected.', );
        } 
        $structure->update([
            'name' => $request->name ?? $structure->name,
            'parent_id' => $request->parent_id ?? $structure->parent_id,
            'status' => $request->status ?? $structure->status, 
        ]); 

        return success_response(null,'Structure updated successfully');  
    } 

    public function destroy($id)
    {
        $structure = AreaStructure::findByUuid($id); 
        AreaStructure::where('parent_id', $structure->id)->update(['parent_id' => null]);
        $structure->delete(); 
        return success_response(null,'Structure deleted successfully');   
    } 

}
