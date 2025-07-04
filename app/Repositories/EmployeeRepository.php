<?php 
namespace App\Repositories;

use App\Models\DesignationLog;
use App\Models\Employee; 
use App\Models\User;
use App\Traits\PaginatorTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeRepository
{
    use PaginatorTrait; 
    public function getAllEmployees($request)
    {
        $user = Auth::user();  
        $selectOnly = $request->boolean('select'); 

        $query = User::with('employee.designationOnDate')
            ->where('company_id', $user->company_id)
            ->where('user_type', 'employee');  

        if ($selectOnly) {
            $employees = $query
                ->select('id', 'name') 
                ->latest()
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                    ];
                });
            return $employees;
        }


        // Step 1: Pagination আগে করব
        $paginated = $this->paginateQuery($query, $request); // 👈 paginateQuery unchanged

        // Step 2: Data mapping (after pagination)
        $mapped = collect($paginated['data'])->map(function ($user) {
            $seniorUserName = null;
            if (!empty($user->senior_user)) {
                $firstSeniorUser = User::find($user->senior_user[0]);
                $seniorUserName = $firstSeniorUser ? $firstSeniorUser->name : null;
            }

            return [
                'uuid' => $user->uuid,
                'employee_id' => $user->employee->employee_id ?? null,
                'profile_image' => $user->profile_image,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'senior_user' => $seniorUserName,
                'designation' => $user->employee->currentDesignation->designation->title ?? null,
            ];
        });

        // Step 3: Replace data with mapped data
        $paginated['data'] = $mapped->values(); // values() দিয়ে index reset করা হয়

        return $paginated;
    }

 



    public function createEmployee($data)
    {
        return Employee::create($data);
    } 
    public function createDesignationLog($data)
    {
        return DesignationLog::create($data);
    }  
 
}
