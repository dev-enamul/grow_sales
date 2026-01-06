<?php 
use App\Http\Controllers\Affiliate\AffiliateController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\VerifyController;
use App\Http\Controllers\Campaign\CampaignController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\Common\CompanyCategoryApiController;
use App\Http\Controllers\Common\CountryApiController;
use App\Http\Controllers\Common\DesignationApiController;
use App\Http\Controllers\Common\DistrictApiController;
use App\Http\Controllers\Common\DivisionApiController;
use App\Http\Controllers\Common\EnamController;
use App\Http\Controllers\Common\RoleApiController;
use App\Http\Controllers\Common\UnionApiController;
use App\Http\Controllers\Common\UpazilaApiController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Employee\DesignationController;
use App\Http\Controllers\Employee\EmployeeController;
use App\Http\Controllers\Employee\EmployeeEditController;
use App\Http\Controllers\Followup\FollowupController;
use App\Http\Controllers\Contact\ContactController;
use App\Http\Controllers\Lead\LeadAssignController;
use App\Http\Controllers\Configuration\LeadCategoryController;
use App\Http\Controllers\Lead\LeadController;
use App\Http\Controllers\Configuration\LeadSourceController;
use App\Http\Controllers\Configuration\ChallengeController;
use App\Http\Controllers\Configuration\OrganizationController;
use App\Http\Controllers\Location\AreaController;
use App\Http\Controllers\Location\AreaStructureController;
use App\Http\Controllers\Configuration\FileController;
use App\Http\Controllers\Configuration\FolderController;
use App\Http\Controllers\Property\LayoutTypeController;
use App\Http\Controllers\Property\ProjectController;
use App\Http\Controllers\Property\UnitController;
use App\Http\Controllers\Service\ServiceCategoryController;
use App\Http\Controllers\Service\ServiceSubCategoryController;
use App\Http\Controllers\Service\ServiceController;
use App\Http\Controllers\Configuration\PropertyTypeController;
use App\Http\Controllers\Configuration\MeasurmentUnitController;

use App\Http\Controllers\Configuration\PropertyUnitController;
use App\Http\Controllers\Configuration\VatSettingController; 
use App\Http\Controllers\Sales\SalesController;
use App\Http\Controllers\User\ProfileUpdateController;
use App\Http\Controllers\User\UserContactController; 
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/ 

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.forgot');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');

Route::post('register', [AuthController::class, 'register']); 
Route::get('company-categories',CompanyCategoryApiController::class);

// Location 
Route::get('countries',CountryApiController::class);
Route::get('divisions',DivisionApiController::class);
Route::get('districts',DistrictApiController::class);
Route::get('upazilas',UpazilaApiController::class);
Route::get('unions',UnionApiController::class);  
 
Route::get('company-verify/{id}',[VerifyController::class,'verify']);
Route::get('files/{file}/download', [FileController::class, 'download'])->name('files.download');
Route::middleware(['auth:sanctum'])->group(function () {

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard.view');
    Route::get('dashboard/chart', [DashboardController::class, 'chartData'])->middleware('permission:dashboard.view');
    Route::get('dashboard/recent-activity', [DashboardController::class, 'recentActivity'])->middleware('permission:dashboard.view');


    // MediaFile 
    Route::resource('folder', FolderController::class)->middleware('permission:configuration.media_view');
    Route::resource('files', FileController::class)->middleware('permission:configuration.media_view'); 
    
    // Property
    Route::resource('project', ProjectController::class)->middleware('permission:property.view'); 
    Route::resource('layout-type', LayoutTypeController::class)->middleware('permission:property.view');
    Route::resource('unit', UnitController::class)->middleware('permission:property.view');

    // Service
    Route::resource('service-category', ServiceCategoryController::class)->middleware('permission:service.view');
    Route::resource('service-sub-category', ServiceSubCategoryController::class)->middleware('permission:service.view');
    Route::resource('service', ServiceController::class)->middleware('permission:service.view');  
     
    // Employee  
    Route::resource('designation', DesignationController::class);
    Route::get('permissions', [PermissionController::class, 'index'])->middleware('permission:designation.edit'); 
    Route::get('designations/{id}/permissions', [PermissionController::class, 'getDesignationPermissions'])->middleware('permission:designation.view');
    Route::post('designations/{id}/permissions', [PermissionController::class, 'updateDesignationPermissions'])->middleware('permission:designation.edit');
    Route::resource('employee', EmployeeController::class)->middleware('permission:employee.view_all|employee.view_own');
    Route::put('employee-designation-update/{uuid}',[EmployeeEditController::class,'updateDesignation'])->middleware('permission:employee.edit');
    Route::put('employee-reporting-update/{uuid}',[EmployeeEditController::class,'updateReporting'])->middleware('permission:employee.edit'); 

    // Attendance
    Route::get('attendance/status', [\App\Http\Controllers\HR\AttendanceController::class, 'status']);
    Route::post('attendance/check-in', [\App\Http\Controllers\HR\AttendanceController::class, 'checkIn']);
    Route::post('attendance/check-out', [\App\Http\Controllers\HR\AttendanceController::class, 'checkOut']);

    // Leave Management
    Route::get('leave-types', [\App\Http\Controllers\HR\LeaveController::class, 'getLeaveTypes']);
    Route::get('my-leave-balance', [\App\Http\Controllers\HR\LeaveController::class, 'myBalance']);
    Route::post('leave-apply', [\App\Http\Controllers\HR\LeaveController::class, 'apply']);
    Route::get('leave-applications', [\App\Http\Controllers\HR\LeaveController::class, 'index']); // Admin & User
    Route::post('leave-applications/{id}/status', [\App\Http\Controllers\HR\LeaveController::class, 'updateStatus']); // Admin

    // HR Settings
    Route::get('hr-settings', [\App\Http\Controllers\HR\HrSettingController::class, 'getSettings']);
    Route::post('hr-settings/general', [\App\Http\Controllers\HR\HrSettingController::class, 'updateGeneral']);
    Route::post('hr-settings/late-rule', [\App\Http\Controllers\HR\HrSettingController::class, 'saveLateRule']);
    Route::delete('hr-settings/late-rule/{id}', [\App\Http\Controllers\HR\HrSettingController::class, 'deleteLateRule']);

    // Payroll
    Route::post('payroll/generate', [\App\Http\Controllers\HR\PayrollController::class, 'generate']); // Admin
    Route::get('payrolls', [\App\Http\Controllers\HR\PayrollController::class, 'index']); // List Sheets
    
    // Salary Structure
    // Salary Structure & Components
    Route::get('salary-components', [\App\Http\Controllers\HR\SalaryStructureController::class, 'getComponents']);
    Route::post('salary-components', [\App\Http\Controllers\HR\SalaryStructureController::class, 'storeComponent']);
    Route::put('salary-components/{id}', [\App\Http\Controllers\HR\SalaryStructureController::class, 'updateComponent']);
    Route::delete('salary-components/{id}', [\App\Http\Controllers\HR\SalaryStructureController::class, 'destroyComponent']);
    Route::get('salary-structure/{userId}', [\App\Http\Controllers\HR\SalaryStructureController::class, 'getUserStructure']);
    Route::post('salary-structure', [\App\Http\Controllers\HR\SalaryStructureController::class, 'updateStructure']);

    // Work Shifts
    Route::apiResource('work-shifts', \App\Http\Controllers\HR\WorkShiftController::class);
    
    // Holiday
    Route::get('holidays', [\App\Http\Controllers\HR\HolidayController::class, 'index']);
    Route::post('holidays', [\App\Http\Controllers\HR\HolidayController::class, 'store']);
    Route::delete('holidays/{id}', [\App\Http\Controllers\HR\HolidayController::class, 'destroy']);


    // Organization  
    Route::resource('organization', OrganizationController::class);
    Route::resource('company', CompanyController::class)->middleware('permission:configuration.company_view');

    // Organization  
    Route::resource('contact', ContactController::class)->middleware('permission:contact.view');

    // Affiliate 
    Route::resource('affiliate', AffiliateController::class)->middleware('permission:affiliate.view_all|affiliate.view_own');
    
    // Lead 
    Route::resource('campaign', CampaignController::class);
    Route::apiResource('lead-category', LeadCategoryController::class)->middleware('permission:configuration.pipeline_view');
    Route::resource('lead-source',LeadSourceController::class)->middleware('permission:configuration.pipeline_view');
    Route::resource('challenge',ChallengeController::class)->middleware('permission:configuration.pipeline_view');
    Route::resource('lead',LeadController::class)->middleware('permission:lead.view_all|lead.view_own');
    Route::post('lead-assign-to/{uuid}',LeadAssignController::class);
    Route::post('lead-contact',[\App\Http\Controllers\Lead\LeadContactController::class,'store']);
    Route::put('lead/{uuid}/products',[\App\Http\Controllers\Lead\LeadController::class,'updateProducts']);
    Route::put('lead/{uuid}/decision-maker',[\App\Http\Controllers\Lead\LeadController::class,'updateDecisionMaker']);
    Route::put('lead/{uuid}/assigned-to',[\App\Http\Controllers\Lead\LeadController::class,'updateAssignedTo']);
    Route::put('lead/{uuid}/affiliate',[\App\Http\Controllers\Lead\LeadController::class,'updateAffiliate']);

    Route::resource('customer', CustomerController::class)->middleware('permission:customer.view_all|customer.view_own');
    Route::get('customer-personal-info',[CustomerController::class,'show']);
    
    // Sales
    Route::post('sales/{uuid}/approve', [SalesController::class, 'approve']);
    Route::resource('sales', SalesController::class)->middleware('permission:sales.view_all|sales.view_own');
    Route::resource('sales-payment-schedule', \App\Http\Controllers\Sales\SalesPaymentScheduleController::class);
    Route::resource('sales-payment', \App\Http\Controllers\Sales\SalesPaymentController::class);
    Route::post('sales-user/bulk-update', [\App\Http\Controllers\Sales\SalesUserController::class, 'bulkUpdate']);
    Route::resource('sales-user', \App\Http\Controllers\Sales\SalesUserController::class);
    Route::get('users', [UserController::class, 'index']);
    
    // Follwup 
    Route::resource('followup',FollowupController::class);

    // User common 
    Route::post('profile-picture-update',[ProfileUpdateController::class,'profile_picture']);
    Route::post('bio-update',[ProfileUpdateController::class,'bio']); 
    // contact   
    Route::get('contacts/{uuid}',[UserContactController::class,'contact_list']);
    Route::post('add-contact',[UserContactController::class,'add_contact']);
    Route::post('upate-contact',[UserContactController::class,'update_contact']);
    Route::get('show-contact',[UserContactController::class,'show_contact']);
    
 
    // Setting Route 
    Route::resource('property-unit',PropertyUnitController::class)->middleware('permission:configuration.properties_view');
    Route::resource('measurment-unit',MeasurmentUnitController::class)->middleware('permission:configuration.measurements_view');
    // Accounting
    Route::resource('bank', \App\Http\Controllers\Configuration\BankController::class);
    Route::resource('account', \App\Http\Controllers\Configuration\AccountController::class);
    Route::resource('payment-reason', \App\Http\Controllers\Configuration\PaymentReasonController::class);
    Route::resource('property-type',PropertyTypeController::class)->middleware('permission:configuration.properties_view');
    Route::resource('vat-setting',VatSettingController::class)->middleware('permission:configuration.vat_view');
    Route::resource('area-structure',AreaStructureController::class)->middleware('permission:location.view');
    Route::resource('area',AreaController::class)->middleware('permission:location.view');
});

// Enam 
Route::get('blood-groups',[EnamController::class,'bloodgroup']);
Route::get('genders',[EnamController::class,'gender']);
Route::get('maritual-statuses',[EnamController::class,'maritualStatus']);
Route::get('priorities',[EnamController::class,'priority']);
Route::get('religions',[EnamController::class,'religion']);
Route::get('statuses',[EnamController::class,'status']);
Route::get('educations',[EnamController::class,'education']);
Route::get('professions',[EnamController::class,'profession']);
Route::get('campaign-types',[EnamController::class,'campaignType']);
Route::get('channels',[EnamController::class,'channel']);
Route::get('organization-types',[EnamController::class,'organizationType']);
Route::get('industries',[EnamController::class,'industry']);


 
