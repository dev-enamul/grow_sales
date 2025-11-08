<?php 
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\VerifyController;
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
use App\Http\Controllers\Employee\EmployeeController;
use App\Http\Controllers\Employee\EmployeeEditController;
use App\Http\Controllers\Followup\FollowupController;
use App\Http\Controllers\Lead\LeadAssignController;
use App\Http\Controllers\Lead\LeadCategoryController;
use App\Http\Controllers\Lead\LeadController;
use App\Http\Controllers\Lead\LeadSourceController;
use App\Http\Controllers\Location\AreaController;
use App\Http\Controllers\Location\AreaStructureController;
use App\Http\Controllers\Property\LayoutTypeController;
use App\Http\Controllers\Property\ProjectController;
use App\Http\Controllers\Property\UnitController;
use App\Http\Controllers\Setting\PropertyTypeController;
use App\Http\Controllers\Setting\MeasurmentUnitController;
use App\Http\Controllers\Setting\PropertyUnitController;
use App\Http\Controllers\Setting\VatSettingController; 
use App\Http\Controllers\User\ProfileUpdateController;
use App\Http\Controllers\User\UserAddressController;
use App\Http\Controllers\User\UserContactController; 
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

Route::post('register', [AuthController::class, 'register']); 
Route::get('company-categories',CompanyCategoryApiController::class);

// Location 
Route::get('countries',CountryApiController::class);
Route::get('divisions',DivisionApiController::class);
Route::get('districts',DistrictApiController::class);
Route::get('upazilas',UpazilaApiController::class);
Route::get('unions',UnionApiController::class);  
 
Route::get('company-verify/{id}',[VerifyController::class,'verify']);
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('roles',RoleApiController::class);
    Route::get('designations',DesignationApiController::class); 
    Route::resource('project', ProjectController::class); 
    Route::resource('layout-type', LayoutTypeController::class);
    Route::resource('unit', UnitController::class);
     
    // Employee
    Route::resource('employee', EmployeeController::class);
    Route::post('existing-employee-data',[EmployeeController::class,'existingEmployeeData']);
    Route::post('employee-designation-update',[EmployeeEditController::class,'updateDesignation']);
    Route::post('employee-reporting-update',[EmployeeEditController::class,'updateReporting']); 
    
    // Lead 
    Route::apiResource('lead-category', LeadCategoryController::class);
    Route::resource('lead',LeadController::class);
    Route::resource('lead-source',LeadSourceController::class);
    Route::resource('customer', CustomerController::class); 
    Route::get('customer-personal-info',[CustomerController::class,'show']);
    Route::post('lead-assign-to',LeadAssignController::class);
    
    // Follwup 
    Route::resource('followup',FollowupController::class);

    // User common 
    Route::post('profile-picture-update',[ProfileUpdateController::class,'profile_picture']);
    Route::post('bio-update',[ProfileUpdateController::class,'bio']);
    // address 
    Route::post('address-update',[UserAddressController::class,'update']);
    Route::get('address/{uuid}',[UserAddressController::class,'show']);
    // contact   
    Route::get('contacts/{uuid}',[UserContactController::class,'contact_list']);
    Route::post('add-contact',[UserContactController::class,'add_contact']);
    Route::post('upate-contact',[UserContactController::class,'update_contact']);
    Route::get('show-contact',[UserContactController::class,'show_contact']);
    
 
    // Setting Route 
    Route::resource('property-unit',PropertyUnitController::class);
    Route::resource('measurment-unit',MeasurmentUnitController::class);
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


 
