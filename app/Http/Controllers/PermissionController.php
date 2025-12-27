<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    // Get all permissions grouped by group_name
    public function index()
    {
        $permissions = Permission::all()->groupBy('group_name');
        return response()->json([
            'status' => true,
            'data' => $permissions
        ]);
    }
 
    public function getDesignationPermissions($id)
    {
        $designation = Designation::with('permissions')->where('uuid', $id)->first();
        
        if (!$designation) { 
            $designation = Designation::with('permissions')->find($id);
        }

        if (!$designation) {
            return response()->json(['status' => false, 'message' => 'Designation not found'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $designation->permissions->pluck('id') // Return array of permission IDs
        ]);
    }

    // Update permissions for a designation
    public function updateDesignationPermissions(Request $request, $id)
    {
        $request->validate([
            'permissions' => 'present|array', // Allow empty array but key must exist
            'permissions.*' => 'exists:permissions,id'
        ]);

        $designation = Designation::where('uuid', $id)->first();
        
        if (!$designation) {
             // Try finding by ID if UUID fails
            $designation = Designation::find($id);
        }
        
        if (!$designation) {
            return response()->json(['status' => false, 'message' => 'Designation not found'], 404);
        }

        try {
            DB::beginTransaction();
            // Sync permissions (detach old, attach new)
            $permissions = $request->input('permissions', []);
            $designation->permissions()->sync($permissions);
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Permissions updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false, 
                'message' => 'Failed to update permissions: ' . $e->getMessage()
            ], 500);
        }
    }
}
