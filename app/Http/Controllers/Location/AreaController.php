<?php

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AreaStructure;
use App\Models\User;
use App\Traits\PaginatorTrait;
use App\Traits\ValidatesParent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AreaController extends Controller
{
    use ValidatesParent; 
    use PaginatorTrait; 
    public function index(Request $request)
    {
        $authUser = User::find(Auth::user()->id);
        $query = Area::where('company_id',$authUser->company_id)->with(['parent', 'areaStructure'])
            ->select('id', 'uuid', 'name', 'parent_id', 'area_structure_id');
 
        $keyword = $request->input('keyword');
        $structure_id = $request->input('structure_id');
        $parent_id = $request->input('parent_id');

        if ($keyword) {
            $query->where('name', 'like', "%$keyword%");
        }
        if ($structure_id) {
            $area_structure = AreaStructure::findByUuid($structure_id);
            $query->where('area_structure_id',$area_structure->id);
        }
        if ($parent_id) { 
            $parent = Area::findByUuid($parent_id);
            $query->where('parent_id',$parent->id);
        }

        $sortBy = $request->input('sort_by');
        $sortOrder = $request->input('sort_order', 'asc');

        $allowedSorts = ['name']; 
        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();  
        }


        if ($request->boolean('select2')) { 
            $areas = $query->limit(10)->get();

            $results = $areas->map(function ($area) { 
                $structureName = optional($area->areaStructure)->name; 
                $parentName = optional($area->parent)->name; 
                $textParts = [$area->name];
            
                if ($structureName) {
                    $textParts[] = $structureName;
                }
            
                $text = implode(' ', $textParts);
            
                if ($parentName) {
                    $text .= "(in $parentName)";
                } 
                return [
                    'id' => $area->uuid,
                    'text' => trim($text),
                ];
            });  

            
            return success_response($results); 
        }

        // Default paginated response
        $paginated = $this->paginateQuery($query, $request);

        $paginated['data'] = $paginated['data']->map(function ($area) {
            return [
                'uuid' => $area->uuid,
                'name' => $area->name,
                'parent_id' => $area->parent_id,
                'parent_name' => optional($area->parent)->name,
                'areaStructure_id' => $area->area_structure_id,
                'areaStructureName' => optional($area->areaStructure)->name ?? '',
            ];
        });
        return success_response($paginated); 
    } 
 
    public function store(Request $request)
    { 
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:areas,uuid',
            'area_structure_id' => 'required|exists:area_structures,uuid', 
            'status' => 'nullable|in:0,1',
        ]);  
 
        $input = $request->all();   
        if ($request->filled('parent_id')) {
            $area = Area::findByUuid($request->parent_id);
            $input['parent_id'] = $area->id;
        } else {
            $input['parent_id'] = null;
        } 
 
        $area_structure = AreaStructure::findByUuid($request->area_structure_id); 
        $input['area_structure_id'] = $area_structure->id; 
        Area::create($input);
        return success_response(null,"Area created successfully."); 
    } 
    
   public function update(Request $request, $id)
    {
        $area = Area::findByUuid($id); 
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'parent_id' => 'nullable|exists:areas,uuid|not_in:' . $area->uuid,
            'area_structure_id' => 'sometimes|required|exists:area_structures,uuid',
            'status' => 'nullable|in:0,1',
        ]); 
        if ($request->filled('parent_id')) {
            $parentArea = Area::findByUuid($request->parent_id);
            if (!$parentArea) {
                return error_response(null, 404, 'Parent area not found');
            }
            if (!$this->isValidParent($area, $parentArea->id)) {
                return error_response(null, 422, 'Invalid parent: circular reference detected.');
            }
            $updateData['parent_id'] = $parentArea->id;
        } else {
            $updateData['parent_id'] = null;
        }

        $updateData = array_merge($updateData ?? [], $request->only(['name', 'status']));

        if ($request->filled('area_structure_id')) {
            $structure = AreaStructure::findByUuid($request->area_structure_id);
            if (!$structure) {
                return error_response(null, 404, 'Area Structure not found');
            }
            $updateData['area_structure_id'] = $structure->id;
        }

        $area->update($updateData);

        return success_response(null, "Area updated successfully.");
    }


 
    public function destroy($id)
    {
        $area = Area::findByUuid($id); 
        $hasChild = Area::where('parent_id', $area->id)->exists(); 
        if ($hasChild) {
            return error_response(null, 422, 'Cannot delete area because it has child data');
        } 
        $area->delete(); 
        return success_response(null, 'Area deleted successfully');
    }

}
