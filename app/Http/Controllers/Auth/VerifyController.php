<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ProductUnit;
use App\Models\VatSetting;
use Illuminate\Http\Request;

class VerifyController extends Controller
{
    public function verify($company_uuid){
        try {
            $company = Company::where('uuid',$company_uuid)->first();
         
            $company->is_verified = true;
            $company->is_active = true;
            $company->save();
    
            $this->createProductUnit($company->id);
            $this->createVatSetting($company->id);
        } catch (\Exception $e) {
            return error_response($e->getMessage(),500);
        }
       

    }

    public function createProductUnit($company_id){
        $units = [
            ['name' => 'Box', 'abbreviation' => 'Box'],
            ['name' => 'CM', 'abbreviation' => 'CM'],
            ['name' => 'DZ', 'abbreviation' => 'DZ'],
            ['name' => 'FT', 'abbreviation' => 'FT'],
            ['name' => 'G', 'abbreviation' => 'G'],
            ['name' => 'IN', 'abbreviation' => 'IN'],
            ['name' => 'KG', 'abbreviation' => 'KG'],
            ['name' => 'KM', 'abbreviation' => 'KM'],
            ['name' => 'LB', 'abbreviation' => 'LB'],
            ['name' => 'MG', 'abbreviation' => 'MG'],
            ['name' => 'ML', 'abbreviation' => 'ML'],
            ['name' => 'M', 'abbreviation' => 'M'],
            ['name' => 'PCS', 'abbreviation' => 'PCS'],
            ['name' => 'SET', 'abbreviation' => 'SET'],
            ['name' => 'YD', 'abbreviation' => 'YD'],
        ];

        $user_id = auth()->id(); 
        foreach ($units as $unit) {
            ProductUnit::create([
                'name' => $unit['name'],
                'abbreviation' => $unit['abbreviation'],
                'company_id' => $company_id,
                'created_by' => $user_id,
                'updated_by' => $user_id,
            ]);
        }

    } 

    public function createVatSetting($company_id){
        $user_id = auth()->id();   
        VatSetting::create([
            'name' => 'Standard VAT Rate',
            'company_id' => $company_id,
            'vat_percentage' => 15.00, 
            'is_active' => true,
            'note' => 'This is the standard VAT rate in Bangladesh for most goods and services.',
            'created_by' => $user_id,
            'updated_by' => $user_id,
        ]);
    }
}
