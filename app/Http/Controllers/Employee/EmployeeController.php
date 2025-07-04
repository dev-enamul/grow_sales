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
                "phone" => $user->phone,
                'email' => $user->email,
                "marital_status" => $user->marital_status,
                'dob' => $user->dob,
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


    public function existingEmployeeData(Request $request){
        $phone = $request->phone;
        $email = $request->email;

        $user = User::where('phone', $phone)
            ->orWhere('email', $email)
            ->with(['userContact', 'userAddress', 'employee', 'reportingUsers'])
            ->first();

        if ($user) {
            $contact = $user->userContact()->first();
    
            $permanentAddress = $user->userAddress()->where('address_type', 'permanent')->first();
            $presentAddress = $user->userAddress()->where('address_type', 'present')->first();
    
            $response = [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'designation_id' => optional($user->employee)->designation_id, 
                'reporting_user_id' => optional($user->reportingUsers)->first()->user_id, 
                'role_id' => $user->role_id,
                'referred_by' => $user->employee ? $user->employee->referred_by : null, 
                'profile_image' => $user->profile_image ?? '',
                'dob' => $user->dob,
                'blood_group' => $user->blood_group,
                'gender' => $user->gender,
    
                'office_phone' => $contact->office_phone ?? '',
                'personal_phone' => $contact->personal_phone ?? '',
                'office_email' => $contact->office_email ?? '',
                'personal_email' => $contact->personal_email ?? '',
                'website' => $contact->website ?? '',
                'whatsapp' => $contact->whatsapp ?? '',
                'imo' => $contact->imo ?? '',
                'facebook' => $contact->facebook ?? '',
                'linkedin' => $contact->linkedin ?? '',
    
                'permanent_country' => $permanentAddress ? $permanentAddress->country : '',
                'permanent_division' => $permanentAddress ? $permanentAddress->division : '',
                'permanent_district' => $permanentAddress ? $permanentAddress->district : '',
                'permanent_upazila_or_thana' => $permanentAddress ? $permanentAddress->upazila_or_thana : '',
                'permanent_zip_code' => $permanentAddress ? $permanentAddress->zip_code : '',
                'permanent_address' => $permanentAddress ? $permanentAddress->address : '',
                'is_same_present_permanent' => $permanentAddress ? $permanentAddress->is_same_present_permanent : false,
    
                'present_country' => $presentAddress ? $presentAddress->country : '',
                'present_division' => $presentAddress ? $presentAddress->division : '',
                'present_district' => $presentAddress ? $presentAddress->district : '',
                'present_upazila_or_thana' => $presentAddress ? $presentAddress->upazila_or_thana : '',
                'present_zip_code' => $presentAddress ? $presentAddress->zip_code : '',
                'present_address' => $presentAddress ? $presentAddress->address : '',
            ]; 
            return response()->json($response);
        } 
        return response()->json(['message' => 'User not found'], 404);
    }
}
