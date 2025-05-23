<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CategoryType;
use App\Models\Company;
use App\Models\MeasurmentUnit;
use App\Models\ProductUnit;
use App\Models\VatSetting;
use Illuminate\Http\Request;

class VerifyController extends Controller
{
    public function verify($company_uuid){
        try {
            $company = Company::where('uuid',$company_uuid)->first();
            if(!$company){
                error_response("Invalid Company");
            }
         
            $company->is_verified = true;
            $company->is_active = true;
            $company->save();
    
            $this->createProductUnit($company->id);
            $this->createVatSetting($company->id);
            $this->createMeasurementUnit($company->id);
            $this->createCategoryType($company->id);
        } catch (\Exception $e) {
            return error_response($e->getMessage(),500);
        }  
    }

    public function createProductUnit($company_id){
        $product_unit = [
            ['name' => 'Flat', 'abbreviation' => 'FLT'],
            ['name' => 'Plot', 'abbreviation' => 'PLT'],
            ['name' => 'Duplex', 'abbreviation' => 'DPX'],
            ['name' => 'Penthouse', 'abbreviation' => 'PTH'],
            ['name' => 'Studio Apartment', 'abbreviation' => 'STD'],
            ['name' => 'Commercial Space', 'abbreviation' => 'CMS'],
            ['name' => 'Office Space', 'abbreviation' => 'OFF'],
            ['name' => 'Retail Shop', 'abbreviation' => 'RSP'],
            ['name' => 'Showroom', 'abbreviation' => 'SHR'],
            ['name' => 'Warehouse', 'abbreviation' => 'WHR'],
            ['name' => 'Parking Space', 'abbreviation' => 'PKS'],
            ['name' => 'Land', 'abbreviation' => 'LND'],
        ];


        $user_id = auth()->id(); 
        foreach ($product_unit as $unit) {
            ProductUnit::create([
                'name' => $unit['name'],
                'abbreviation' => $unit['abbreviation'],
                'company_id' => $company_id,
                'created_by' => $user_id,
                'updated_by' => $user_id,
            ]);
        }

    } 

    public function createMeasurementUnit($company_id){
        $measurment_units = [
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
        foreach ($measurment_units as $unit) {
            MeasurmentUnit::create([
                'name' => $unit['name'],
                'abbreviation' => $unit['abbreviation'],
                'company_id' => $company_id,
                'created_by' => $user_id,
                'updated_by' => $user_id,
            ]);
        }

    } 

    public function createCategoryType($company_id){
        $category_types = [
            ['name' => 'Residential'],
            ['name' => 'Commercial'],
            ['name' => 'Land'],
        ];  


        $user_id = auth()->id(); 
        foreach ($category_types as $tupe) {
            CategoryType::create([
                'name' => $tupe['name'], 
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
