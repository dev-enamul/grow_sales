<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmployeeService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeEditController extends Controller
{
    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    public function updateDesignation(Request $request, $uuid){
        DB::beginTransaction(); 
        try {
            $validated = $request->validate([
                'designation_id' => 'required|exists:designations,id',
                'activated_at' => 'required',
            ]);

            $designation_id = $validated['designation_id'];
            $start_date = Carbon::parse($validated['activated_at']);
            $auth_user = Auth::user();
 
            $user = User::where('uuid', $uuid)
                        ->where('company_id', $auth_user->company_id)
                        ->first();

            if (!$user) {
                return error_response('User not found.', 404); 
            } 
           
            $result = $this->employeeService->updateEmployeeDesignation($user, $designation_id, $start_date, $auth_user);
 
            DB::commit(); 
            return success_response($result['message']);  
        } catch (Exception $e) {
            DB::rollBack(); 
            return error_response($e->getMessage(),500); 
        }
    } 

    public function updateReporting(Request $request, $uuid) {
        $request->validate([
            'reporting_id' => 'nullable|exists:users,id',
        ]);
    
        DB::beginTransaction();
    
        try { 
            $authUser = Auth::user(); 
            $user = User::where('uuid', $uuid)
                        ->where('company_id', $authUser->company_id)
                        ->first();
            if (!$user) {
                return error_response("User not found.", 404);
            }
     
            $reportingUserId = $request->reporting_id;
            $result = $this->employeeService->updateEmployeeReporting($user, $reportingUserId, $authUser);
     
            // Check if no change was made
            if (isset($result['no_change']) && $result['no_change']) {
                DB::commit();
                return success_response($result['message']);
            }
     
            DB::commit(); 
            return success_response($result['message']); 
        } catch (\Exception $e) { 
            DB::rollBack(); 
            return error_response($e->getMessage(), 500);
        }
    }
    
    

}
