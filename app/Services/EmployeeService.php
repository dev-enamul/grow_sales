<?php 
namespace App\Services;

use App\Repositories\EmployeeRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Helpers\ReportingService;
use App\Models\Employee;

class EmployeeService
{
    protected $employeeRepo;
    protected $userRepo;

    public function __construct(EmployeeRepository $employeeRepo, UserRepository $userRepo)
    {
        $this->employeeRepo = $employeeRepo;
        
        $this->userRepo = $userRepo;
    }

    public function getAllEmployees()
    {
        return $this->employeeRepo->getAllEmployees();
    }

    public function createEmployee($request)
    {
        DB::beginTransaction();
        try {
            $authUser = $this->userRepo->findUserById(Auth::id());
            $user = $this->userRepo->createUser([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make('12345678'),
                'user_type' => 'employee',
                'profile_image' => $request->file('profile_image') ? $request->file('profile_image')->store('profile_images', 'public') : null,
                'role_id' => $request->role_id,
                'company_id' => $authUser->company_id,
            ]);

            // Create User Contact and Address
            $this->userRepo->createUserContact([
                'user_id' => $user->id,
                'name' => $request->name ?? $request->user_name,
                'office_phone' => $request->phone,
                'personal_phone' => $request->personal_phone,
                'office_email' => $request->email,
                'personal_email' => $request->personal_email,
                'emergency_contact_number' => $request->emergency_contact_number,
                'emergency_contact_person' => $request->emergency_contact_person,
            ]);

            $this->userRepo->createUserAddress([
                'user_id' => $user->id,
                'country_id' => $request->country_id,
                'division_id' => $request->division_id,
                'district_id' => $request->district_id,
                'upazila_id' => $request->upazila_id,
                'address' => $request->address,
            ]);

            $this->userRepo->createUserReporting([
                'user_id' => $user->id,
                'reporting_user_id' => $request->reporting_user_id,
                'start_date' => now(), 
            ]);

            // Create Employee
            $employee = $this->employeeRepo->createEmployee([
                'user_id' => $user->id,
                'employee_id' => Employee::generateNextEmployeeId(),
                'status' => 1,
            ]);

            // Assign Designation
            $this->employeeRepo->createEmployeeDesignation([
                'user_id' => $user->id,
                'employee_id' => $employee->id,
                'designation_id' => $request->designation_id,
                'start_date' => now(),
            ]);

            // Reporting
            $user->senior_user = ReportingService::getAllSenior($user->id);
            $user->junior_user = ReportingService::getAllJunior($user->id);
            $user->save(); 
            DB::commit();
            return ['success' => true, 'message' => 'Employee created successfully! Login credentials have been sent to the provided email address.'];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }  

    public function show($uuid){
        return $this->userRepo->findUserByUuId($uuid);
    }

    public function updateEmployee($id, $request){
        
    }
}
