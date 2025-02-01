<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\DesignationLog;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeEditController extends Controller
{
    public function updateDesignation(Request $request){
        DB::beginTransaction(); 
        try {
            $validated = $request->validate([
                'uuid' => 'required|uuid',
                'designation_id' => 'required|exists:designations,id',
                'activated_at' => 'required',
            ]);

            $user_uuid = $validated['uuid'];
            $designation_id = $validated['designation_id'];
            $start_date = Carbon::parse($validated['activated_at']);
            $auth_user = Auth::user();
 
            $user = User::where('uuid', $user_uuid)
                        ->where('company_id', $auth_user->company_id)
                        ->first();

            if (!$user) {
                return error_response('User not found.', 404); 
            } 
            $employee = $user->employee;
            $employee->designation_id = $designation_id;
            $employee->save();
 
            $currentDesignation = $employee->currentDesignation()->first();
            if ($currentDesignation) {
                $currentDesignation->end_date = $start_date->subDay();;
                $currentDesignation->updated_by = $auth_user->id;
                $currentDesignation->save();
            }
 
            DesignationLog::create([
                'user_id' => $user->id,
                'employee_id' => $employee->id,
                'designation_id' => $designation_id,
                'start_date' => $start_date,
                'created_by' => $auth_user->id,
            ]);
 
            DB::commit(); 
            return success_response('Designation updated successfully.');  
        } catch (Exception $e) {
            DB::rollBack(); 
            return error_response($e->getMessage(),500); 
        }
    }

}
