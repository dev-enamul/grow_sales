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
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    public function index(Request $request)
    {
        try {
            $data = $this->employeeService->getAllEmployees($request);
            return success_response($data);
        } catch (\Exception $e) {
            return error_response($e->getMessage(),500);
        }
    }

    public function store(EmployeeStoreRequest $request)
    {
        try {
            $existingUser = AuthService::checkExistingActiveEmployee($request->email);
            if ($existingUser) {
                return error_response("Already associated with ".$existingUser->company->name." Please resign first or use another email.", 409);
            } 
            
            $result = $this->employeeService->createEmployee($request);
            $user = $result['user'];
            
            // Send password setup email
            $emailSent = $this->sendPasswordSetupEmail($user);   

            // If email failed to send, set default password
            if (!$emailSent) { 
                return success_response(null, 'Employee created successfully but email not sent');
            }
            
            return success_response(null, 'Employee created successfully! Password setup email has been sent to the employee.');
        } catch (\Exception $e) {
            return error_response($e->getMessage(),500);
        }
    }

    /**
     * Send password setup email to employee
     * 
     * @param User $user
     * @return bool Returns true if email sent successfully, false otherwise
     */
    private function sendPasswordSetupEmail(User $user): bool
    {
        // Use common function from AuthService (isForgotPassword = false for initial setup)
        return AuthService::sendPasswordResetEmail($user, false);
    }  

    public function show($uuid){ 
        try {  
            $user = User::with(['employee'])
                        ->where('uuid',$uuid)->first();

            if (!$user) {
                return error_response('User not found', 404);
            } 
            return success_response([
                "name" => $user->name, 
                'designation' => $user->employee->currentDesignation->designation->title??"",
                'profile_image' => $user->profile_image,
                'profile_image_url' => getFileUrl($user->profile_image),
                "phone" => $user->phone,
                'email' => $user->email,
                "marital_status" => $user->marital_status,
                'dob' => formatDate($user->dob),
                'blood_group' => $user->blood_group,
                'gender' => $user->gender,
                'senior_user' => json_decode($user->senior_user??"[]")
            ]);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
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
