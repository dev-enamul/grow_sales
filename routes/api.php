<?php 
use App\Http\Controllers\Affiliate\AffiliateController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\VerifyController;
use App\Http\Controllers\Campaign\CampaignController;
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
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('dashboard/chart', [DashboardController::class, 'chartData']);
    Route::get('dashboard/recent-activity', [DashboardController::class, 'recentActivity']);


    // MediaFile 
    Route::resource('folder', FolderController::class);
    Route::resource('files', FileController::class); 
    
    // Property
    Route::resource('project', ProjectController::class); 
    Route::resource('layout-type', LayoutTypeController::class);
    Route::resource('unit', UnitController::class);

    // Service
    Route::resource('service-category', ServiceCategoryController::class);
    Route::resource('service-sub-category', ServiceSubCategoryController::class);
    Route::resource('service', ServiceController::class);  
     
    // Employee  
    Route::resource('designation', DesignationController::class);
    Route::resource('employee', EmployeeController::class);
    Route::put('employee-designation-update/{uuid}',[EmployeeEditController::class,'updateDesignation']);
    Route::put('employee-reporting-update/{uuid}',[EmployeeEditController::class,'updateReporting']); 

    // Organization  
    Route::resource('organization', OrganizationController::class);

    // Organization  
    Route::resource('contact', ContactController::class);

    // Affiliate 
    Route::resource('affiliate', AffiliateController::class);
    
    // Lead 
    Route::resource('campaign', CampaignController::class);
    Route::apiResource('lead-category', LeadCategoryController::class);
    Route::resource('lead-source',LeadSourceController::class);
    Route::resource('challenge',ChallengeController::class);
    Route::resource('lead',LeadController::class);
    Route::post('lead-assign-to/{uuid}',LeadAssignController::class);
    Route::post('lead-contact',[\App\Http\Controllers\Lead\LeadContactController::class,'store']);
    Route::put('lead/{uuid}/products',[\App\Http\Controllers\Lead\LeadController::class,'updateProducts']);
    Route::put('lead/{uuid}/decision-maker',[\App\Http\Controllers\Lead\LeadController::class,'updateDecisionMaker']);
    Route::put('lead/{uuid}/assigned-to',[\App\Http\Controllers\Lead\LeadController::class,'updateAssignedTo']);
    Route::put('lead/{uuid}/affiliate',[\App\Http\Controllers\Lead\LeadController::class,'updateAffiliate']);

    Route::resource('customer', CustomerController::class);
    Route::get('customer-personal-info',[CustomerController::class,'show']);
    
    // Sales
    Route::post('sales/{uuid}/approve', [SalesController::class, 'approve']);
    Route::resource('sales', SalesController::class);
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
    Route::resource('property-unit',PropertyUnitController::class);
    Route::resource('measurment-unit',MeasurmentUnitController::class);
    // Accounting
    Route::resource('bank', \App\Http\Controllers\Configuration\BankController::class);
    Route::resource('account', \App\Http\Controllers\Configuration\AccountController::class);
    Route::resource('payment-reason', \App\Http\Controllers\Configuration\PaymentReasonController::class);
    Route::resource('property-type',PropertyTypeController::class);
    Route::resource('vat-setting',VatSettingController::class);
    Route::resource('area-structure',AreaStructureController::class);
    Route::resource('area',AreaController::class);
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


 
