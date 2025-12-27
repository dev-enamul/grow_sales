<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class VerifyController extends Controller
{
    public function verify($company_uuid){
        try {
            $company = Company::where('uuid',$company_uuid)->first();
            if(!$company){
                return error_response("Invalid Company");
            }
         
            $company->is_verified = true;
            $company->is_active = true;
            $company->save();
            
            // Note: Seeding is now handled by CompanyObserver::updated
            
            return success_response(null, "Company Verified Successfully");
        } catch (\Exception $e) {
            return error_response($e->getMessage(),500);
        }  
    }
}
