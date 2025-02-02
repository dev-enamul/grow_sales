<?php 
namespace App\Services;

use App\Repositories\EmployeeRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Helpers\ReportingService;
use App\Models\Employee;
use App\Models\User;

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
                'company_id'    => $authUser->company_id,
                'dob'           => $request->dob, 
                'marital_status' => $request->marital_status,
                'blood_group'   => $request->blood_group, 
                'gender'        => $request->gender, 
                'created_by'    => $authUser->id,
            ]); 

            // Create User Contact and Address
            $this->userRepo->createUserContact([
                'user_id'           => $user->id,
                'name'              => $request->name,
                'office_phone'      => $request->office_phone,
                'personal_phone'    => $request->personal_phone,
                'office_email'      => $request->office_email,
                'personal_email'    => $request->personal_email,
                'website'           => $request->website,
                'whatsapp'          => $request->whatsapp,
                'imo'               => $request->imo,
                'facebook'          => $request->facebook,
                'linkedin'          => $request->linkedin,
                'created_by'    => $authUser->id,
            ]);

            if(isset($request->permanent_country) || isset($request->permanent_zip_code) ||  isset($request->permanent_address)){
                $this->userRepo->createUserAddress([
                    'user_id' => $user->id,
                    'address_type'      => "permanent",
                    'country'           => $request->permanent_country,
                    'division'          => $request->permanent_division,
                    'district'          => $request->permanent_district,
                    'upazila_or_thana'  => $request->permanent_upazila_or_thana,
                    "zip_code"          => $request->permanent_zip_code,
                    'address'           => $request->permanent_address, 
                    "is_same_present_permanent" => $request->is_same_present_permanent,
                    'created_by'    => $authUser->id,
               ]);

               if(!$request->is_same_present_permanent){
                    $this->userRepo->createUserAddress([
                        'user_id' => $user->id,
                        'address_type'      => "present",
                        'country'           => $request->present_country,
                        'division'          => $request->present_division,
                        'district'          => $request->present_district,
                        'upazila_or_thana'  => $request->present_upazila_or_thana,
                        "zip_code"          => $request->present_zip_code,
                        'address'           => $request->present_address, 
                        "is_same_present_permanent" => $request->is_same_present_permanent,
                        'created_by'    => $authUser->id,
                    ]); 
                }
            }
            
              // Create Employee
            $employee = $this->employeeRepo->createEmployee([
                'user_id' => $user->id,
                'employee_id' => Employee::generateNextEmployeeId(),
                'designation_id'=> $request->designation_id, 
                'referred_by'   => $request->referred_by, 
                'created_by'    => Auth::user()->id
            ]);

            $reporting_user = User::where('uuid',$request->reporting_user_id)->first();
            $this->userRepo->createUserReporting([
                'user_id' => $user->id,
                'reporting_user_id' => $reporting_user->id,
                'start_date' => now(), 
                'created_by'    => $authUser->id,
            ]);

          

            // Assign Designation
            $this->employeeRepo->createDesignationLog([
                'user_id' => $user->id,
                'employee_id' => $employee->id,
                'designation_id' => $request->designation_id,
                'start_date' => now(),
                'created_by'    => $authUser->id,
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
