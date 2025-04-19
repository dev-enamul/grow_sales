<?php

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use App\Http\Requests\Location\StoreZoneRequest;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ZoneController extends Controller
{ 
    public function index()
    {
        $zones = Zone::latest()->get();
        return success_response($zones, 'Zone list retrieved successfully.');
    }

    public function store(StoreZoneRequest $request)
    {
        $zone = Zone::create([
            'name' => $request->name, 
            'description' => $request->description,
            'status' => $request->status ?? 1 
        ]); 
        return success_response($zone, 'Zone created successfully.');
    }

    public function update(Request $request, $id)
    {
        $zone = Zone::find($id); 
        if (!$zone) {
            return error_response(null, 404, 'Zone not found.');
        } 

        $zone->update([
            'name' => $request->name, 
            'description' => $request->description,
            'status' => $request->status ?? $zone->status 
        ]);

        return success_response($zone, 'Zone updated successfully.');
    }

    public function destroy($id)
    {
        $zone = Zone::find($id); 
        if (!$zone) {
            return error_response(null, 404, 'Zone not found.');
        }   
        $zone->delete(); 
        return success_response(null, 'Zone deleted successfully.');
    }
}
