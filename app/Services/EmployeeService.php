<?php 
namespace App\Services;

use App\Repositories\EmployeeRepository;
use App\Repositories\UserRepository;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Helpers\ReportingService;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
class EmployeeService

{
    protected $employeeRepo;
    protected $userRepo;

    public function __construct(EmployeeRepository $employeeRepo, UserRepository $userRepo)
    {
        $this->employeeRepo = $employeeRepo;
        $this->userRepo = $userRepo;
    }

    public function getAllEmployees($request)
    {
        return $this->employeeRepo->getAllEmployees($request);
    }

    public function createEmployee($request)
    {
        DB::beginTransaction();
        try {
            $authUser = $this->userRepo->findUserById(Auth::id());
            
            // Create User without password (employee will set it via email)
            $user = $this->userRepo->createUser([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make('123456'), 
                'user_type' => 'employee',
                'company_id' => $authUser->company_id,
                'created_by' => $authUser->id,
            ]); 

            // Create Employee
            $employee = $this->employeeRepo->createEmployee([
                'user_id' => $user->id,
                'employee_id' => Employee::generateNextEmployeeId(),
                'referred_by' => $request->referred_by,
                'created_by' => $authUser->id
            ]);

            // Assign Designation
            $this->employeeRepo->createDesignationLog([
                'user_id' => $user->id,
                'employee_id' => $employee->id,
                'designation_id' => $request->designation_id,
                'start_date' => now(),
                'created_by' => $authUser->id,
            ]);

            // Create Reporting Relationship
            if ($request->reporting_id) {
                $reportingUser = User::find($request->reporting_id);
                if ($reportingUser) {
                    $this->userRepo->createUserReporting([
                        'user_id' => $user->id,
                        'reporting_user_id' => $reportingUser->id,
                        'start_date' => now(), 
                        'created_by' => $authUser->id,
                    ]);
                }
            }

            // Update Reporting Hierarchy
            $user->senior_user = ReportingService::getAllSenior($user->id);
            $user->junior_user = ReportingService::getAllJunior($user->id);
            $user->save(); 

            DB::commit();
            return ['success' => true, 'message' => 'Employee created successfully!', 'user' => $user];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }  

    public function show($uuid){
        return $this->userRepo->findUserByUuId($uuid);
    }

    /**
     * Update employee designation
     * 
     * @param User $user
     * @param int $designationId
     * @param \Carbon\Carbon|null $startDate
     * @param User|null $authUser
     * @return array
     * @throws \Exception
     */
    public function updateEmployeeDesignation(User $user, int $designationId, $startDate = null, $authUser = null)
    {
        if (!$authUser) {
            $authUser = $this->userRepo->findUserById(Auth::id());
        }

        if (!$user->employee) {
            throw new \Exception('User is not an employee');
        }

        $startDate = $startDate ?: now();

        // Get current designation
        $currentDesignation = $user->employee->currentDesignation()->first();
        
        if ($currentDesignation) {
            // End current designation
            $currentDesignation->end_date = is_object($startDate) ? $startDate->copy()->subDay() : now()->subDay();
            $currentDesignation->updated_by = $authUser->id;
            $currentDesignation->save();
        }

        // Create new designation log
        $this->employeeRepo->createDesignationLog([
            'user_id' => $user->id,
            'employee_id' => $user->employee->id,
            'designation_id' => $designationId,
            'start_date' => is_object($startDate) ? $startDate : now(),
            'created_by' => $authUser->id,
        ]);

        return ['success' => true, 'message' => 'Designation updated successfully'];
    }

    /**
     * Update employee reporting relationship
     * 
     * @param User $user
     * @param int|null $reportingUserId
     * @param User|null $authUser
     * @return array
     * @throws \Exception
     */
    public function updateEmployeeReporting(User $user, $reportingUserId = null, $authUser = null)
    {
        if (!$authUser) {
            $authUser = $this->userRepo->findUserById(Auth::id());
        }

        // Get active reporting relationship
        $activeReportingUser = $user->reportingUsers()
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            })
            ->first();

        // If reporting_user_id is null, remove existing reporting relationship
        if ($reportingUserId === null) {
            if ($activeReportingUser) {
                $oldReportingUser = User::find($activeReportingUser->reporting_user_id);
                $activeReportingUser->end_date = now()->subDay();
                $activeReportingUser->save();

                // Update Reporting Hierarchy
                $this->updateReportingHierarchy($user);
                
                // Update old reporting user's hierarchy if exists
                if ($oldReportingUser) {
                    $this->updateReportingHierarchy($oldReportingUser);
                }
            }
            return ['success' => true, 'message' => 'Reporting relationship removed successfully'];
        }

        $reportingUser = User::find($reportingUserId);
        if (!$reportingUser) {
            throw new \Exception('Reporting user not found');
        }

        // Validation checks
        if ($user->id == $reportingUser->id) {
            throw new \Exception('You cannot select yourself as a reporting user');
        }

        if (in_array($reportingUser->id, json_decode($user->junior_user ?? "[]"))) {
            throw new \Exception("You cannot select {$reportingUser->name} as a reporting user, as they are already your junior");
        }

        // Check if already up to date
        if ($activeReportingUser && $activeReportingUser->reporting_user_id == $reportingUser->id) {
            return ['success' => true, 'message' => 'The reporting user is already up to date', 'no_change' => true];
        }

        // Store old reporting user for hierarchy update
        $oldReportingUser = null;
        if ($activeReportingUser) {
            $oldReportingUser = User::find($activeReportingUser->reporting_user_id);
            $activeReportingUser->end_date = now()->subDay();
            $activeReportingUser->save();
        }

        // Create new reporting relationship
        $this->userRepo->createUserReporting([
            'user_id' => $user->id,
            'reporting_user_id' => $reportingUser->id,
            'start_date' => now(),
            'created_by' => $authUser->id,
        ]);

        // Update Reporting Hierarchy for all affected users
        $this->updateReportingHierarchy($user);
        
        // Update old reporting user's hierarchy if exists
        if ($oldReportingUser) {
            $this->updateReportingHierarchy($oldReportingUser);
        }
        
        // Update new reporting user's hierarchy
        $this->updateReportingHierarchy($reportingUser);

        return ['success' => true, 'message' => 'Reporting user updated successfully'];
    }

    /**
     * Update reporting hierarchy (senior_user and junior_user)
     * 
     * @param User $user
     * @return void
     */
    public function updateReportingHierarchy(User $user)
    {
        $user->senior_user = ReportingService::getAllSenior($user->id);
        $user->junior_user = ReportingService::getAllJunior($user->id);
        $user->save();
    }

    public function updateEmployee($id, $request){
        DB::beginTransaction();
        try {
            $authUser = $this->userRepo->findUserById(Auth::id());
            
            // Find user by UUID
            $user = $this->userRepo->findUserByUuId($id);
            if (!$user) {
                throw new \Exception('User not found');
            }

            // Check if user belongs to same company
            if ($user->company_id !== $authUser->company_id) {
                throw new \Exception('Unauthorized access');
            }

            // Check if user is an employee
            if ($user->user_type !== 'employee' || !$user->employee) {
                throw new \Exception('User is not an employee');
            }

            // Check if email is already taken by another active employee (excluding current user)
            if ($user->email !== $request->email) {
                $existingUser = AuthService::checkExistingActiveEmployee($request->email);
                if ($existingUser && $existingUser->id !== $user->id) {
                    throw new \Exception("Email is already associated with " . $existingUser->company->name . ". Please use another email.");
                }
            }

            // Update User basic information
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->updated_by = $authUser->id;
            $user->save();

            // Update Employee referred_by if provided
            if ($request->has('referred_by')) {
                $user->employee->referred_by = $request->referred_by;
                $user->employee->updated_by = $authUser->id;
                $user->employee->save();
            }

            // Update Designation if changed
            $currentDesignation = $user->employee->currentDesignation()->first();
            if (!$currentDesignation || $currentDesignation->designation_id != $request->designation_id) {
                $this->updateEmployeeDesignation($user, $request->designation_id, now(), $authUser);
            }

            // Update Reporting Relationship
            // Check if reporting_user_id has changed or if it's being set to null
            $currentReportingUser = $user->reportingUsers()
                ->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', now());
                })
                ->first();
            
            $currentReportingUserId = $currentReportingUser ? $currentReportingUser->reporting_user_id : null;
            $newReportingUserId = $request->reporting_id ?? null;
            
            // Only update if reporting_user_id has changed
            if ($currentReportingUserId != $newReportingUserId) {
                $this->updateEmployeeReporting($user, $newReportingUserId, $authUser);
            }

            DB::commit();
            return ['success' => true, 'message' => 'Employee updated successfully!'];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
