<?php

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Traits\ManualPaginationTrait;
use App\Traits\PaginatorTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegionController extends Controller
{ 
    use PaginatorTrait;
    public function index(Request $request)
    {
        $query = Region::with('zone')->orderByDesc('id');  
        if ($request->zone_id) {
            $query->where('zone_id', $request->zone_id);
        }

        if ($request->name) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->select) {
            $regions = $query->select('id', 'name')->take(10)->get();
            return success_response($regions, 'Region list for select dropdown.');
        }

        $paginationData = $this->paginateQuery($query, $request);
        return success_response($paginationData, 'Region list retrieved successfully.');
    } 

    public function store(Request $request)
    {
        $request->validate([
            'zone_id' => 'nullable|exists:zones,id',
            'name' => 'required|string|max:255|unique:regions,name',
            'description' => 'nullable|string',
            'status' => 'nullable|boolean',
        ]);

        $region = Region::create([
            'zone_id' => $request->zone_id,
            'name' => $request->name,
            'slug' => getSlug(new Region(), $request->name),
            'description' => $request->description,
            'status' => $request->status ?? 1,
            'created_by' => Auth::user()->id,
        ]); 
        return success_response($region, 'Region created successfully.');
    }

    public function update(Request $request, $id)
    {
        $region = Region::find($id);

        if (!$region) {
            return error_response(null, 404, 'Region not found.');
        }

        $request->validate([
            'zone_id' => 'nullable|exists:zones,id',
            'name' => 'required|string|max:255|unique:regions,name,' . $region->id,
            'description' => 'nullable|string',
            'status' => 'nullable|boolean',
        ]);

        $region->update([
            'zone_id' => $request->zone_id,
            'name' => $request->name,
            'slug' => getSlug(new Region(),$request->name),
            'description' => $request->description,
            'status' => $request->status ?? $region->status,
            'updated_by' => Auth::id(),
        ]);

        return success_response($region, 'Region updated successfully.');
    }

    public function destroy($id)
    {
        $region = Region::find($id); 
        if (!$region) {
            return error_response(null, 404, 'Region not found.');
        } 
        $region->deleted_by = Auth::id();
        $region->save();
        $region->delete();

        return success_response(null, 'Region deleted successfully.');
    }
 
}
