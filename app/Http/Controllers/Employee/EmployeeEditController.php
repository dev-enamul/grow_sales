<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\DesignationLog;
use App\Models\User;
use App\Models\UserReporting;
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
           
            $currentDesignation = $user->employee->currentDesignation()->first();
            if ($currentDesignation) {
                $currentDesignation->end_date = $start_date->subDay();
                $currentDesignation->updated_by = $auth_user->id;
                $currentDesignation->save();
            }
 
            DesignationLog::create([
                'user_id' => $user->id,
                'employee_id' => $user->employee->id,
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

    public function updateReporting(Request $request) {
        $request->validate([
            'uuid' => 'required|exists:users,uuid',
            'reporting_uuid' => 'required|exists:users,uuid',
        ]);
    
        DB::beginTransaction();
    
        try { 
            $authUser = Auth::user()->id; 
            $user = User::where('uuid', $request->uuid)->first();
            if (!$user) {
                return error_response("User not found.", 404);
            }
     
            $reportingUser = User::where('uuid', $request->reporting_uuid)->first();
            if (!$reportingUser) {
                return error_response("Reporting user not found.", 404);
            }
     
            if ($user->id == $reportingUser->id) {
                return error_response("You cannot select yourself as a reporting user.", 400);
            } 
            
            if (in_array($reportingUser->id, json_decode($user->junior_user??"[]"))) {
                return error_response("You cannot select {$reportingUser->name} as a reporting user, as they are already your junior.", 400);
            }
     
            $activeReportingUser = $user->reportingUsers()
                ->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', now());
                })
                ->first();
 
            if ($activeReportingUser) { 
                if ($activeReportingUser->reporting_user_id == $reportingUser->id) {
                    return success_response("The reporting user is already up to date.");
                } 
                $activeReportingUser->end_date = now()->subDay();
                $activeReportingUser->save();
            }
     
            UserReporting::create([
                'user_id' => $user->id,
                'reporting_user_id' => $reportingUser->id,
                'start_date' => now(),
                'created_by' => $authUser,
            ]);
     
            DB::commit(); 
            return success_response("Reporting user updated successfully."); 
        } catch (\Exception $e) { 
            DB::rollBack(); 
            return error_response($e->getMessage(), 500);
        }
    }
    
    

}
