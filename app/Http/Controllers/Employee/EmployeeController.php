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
                $existingUserErrorMessage = "Already associated with ".$existingUser->company->name." Please resign first or use another email.";
                return error_response( $existingUserErrorMessage, 409,  $existingUserErrorMessage);
            } 
            
            $result = $this->employeeService->createEmployee($request);
            $user = $result['user'];
            
            // Send password setup email
            $emailSent = $this->sendPasswordSetupEmail($user);   

            // If email failed to send, set default passwordP
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
            $user = User::with(['currentDesignation.designation', 'reportingUsers'])
                        ->where('uuid',$uuid)->first();

            if (!$user) {
                return error_response('User not found', 404);
            }

            // Get current designation ID
            $currentDesignation = $user->currentDesignation;
            $designationId = $currentDesignation ? $currentDesignation->designation_id : null;

            // Get current reporting user
            $currentReportingUser = $user->reportingUsers()
                ->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', now());
                })
                ->first();
            $reportingUserId = $currentReportingUser ? $currentReportingUser->reporting_user_id : null;

            return success_response([
                "uuid" => $user->uuid,
                "name" => $user->name, 
                'designation' => $currentDesignation ? $currentDesignation->designation->title : "",
                'designation_id' => $designationId,
                'profile_image' => $user->profile_image,
                'profile_image_url' => getFileUrl($user->profile_image),
                "phone" => $user->phone,
                'email' => $user->email,
                "marital_status" => $user->marital_status,
                'dob' => $user->dob ? formatDate($user->dob) : null,
                'blood_group' => $user->blood_group,
                'gender' => $user->gender,
                'referred_by' => $user->referred_by ?? null,
                'reporting_id' => $reportingUserId,
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
        }catch(\Illuminate\Validation\ValidationException $e){
            return error_response($e->errors(), 422, 'Validation failed');
        }catch(Exception $e){
            return error_response($e->getMessage(), 500, $e->getMessage());
        }
    }

    public function destroy($uuid){
        try{
            $result = $this->employeeService->deleteEmployee($uuid);
            return success_response(null,$result['message']);
        }catch(Exception $e){
            return error_response($e->getMessage(), 500, $e->getMessage());
        }
    }

 
}
