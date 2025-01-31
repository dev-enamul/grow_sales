<?php 
namespace App\Http\Controllers\Employee;
 
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\EmployeeStoreRequest;
use App\Http\Requests\Employee\EmployeeUpdateRequest; 
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\EmployeeService;
use Exception;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    public function index()
    {
        try {
            $data = $this->employeeService->getAllEmployees();
            return success_response($data);
        } catch (\Exception $e) {
            return error_response($e->getMessage(),500);
        }
    }

    public function store(EmployeeStoreRequest $request)
    {
        try {
            $existingUser = AuthService::checkExistingActiveEmployee($request->user_email);
            if ($existingUser) {
                return error_response("Already associated with ".$existingUser->company->name." Please resign first or use another email.", 409);
            } 
            
            $result = $this->employeeService->createEmployee($request);
            return success_response(null, $result['message']);
        } catch (\Exception $e) {
            return error_response($e->getMessage(),500);
        }
    }  

    public function show($id){
        try{ 
            $user = $this->employeeService->show($id);
            return success_response($user);
        }catch(Exception $e){
            return error_response($e->getMessage(),500);
        }
        
    }

    public function update(EmployeeUpdateRequest $request, $id){
        try{
            $result =  $this->employeeService->updateEmployee($id, $request);
            return success_response(null,$result['message']);
        }catch(Exception $e){
            return error_response($e->getMessage(),500);
        }
    }
}
