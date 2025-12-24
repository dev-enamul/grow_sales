<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\Employee;
use Database\Seeders\AreaSeeder;
use Database\Seeders\AreaStructureSeeder;
use Database\Seeders\DesignationSeeder;
use Database\Seeders\LeadCategorySeeder;
use Database\Seeders\LeadSourceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Artisan;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        $leadSourceSeeder = new LeadSourceSeeder($company->id);
        $leadSourceSeeder->run(); 

        // // Create Lead Category 
        $leadCategorySeeder = new LeadCategorySeeder($company->id);
        $leadCategorySeeder->run(); 

        // // Create Designation 
        $designationSeeder = new DesignationSeeder($company->id);
        $designationSeeder->run();

        // // Create Role 
        $roleSeeder = new RoleSeeder($company->id);
        $roleSeeder->run();

        // // Create Default Accounts
        $accountSeeder = new \Database\Seeders\AccountSeeder($company->id);
        $accountSeeder->run();

        // // Create Payment Reasons (Booking, Down Payment, Installments)
        $paymentReasonSeeder = new \Database\Seeders\PaymentReasonSeeder($company->id);
        $paymentReasonSeeder->run();

        // // Create Area Structure (Country -> Division -> District -> Upazila -> Union)
        // $areaStructureSeeder = new AreaStructureSeeder($company->id);
        // $areaStructureSeeder->run();

        // // Create Areas (matching Area Structures)
        // $areaSeeder = new AreaSeeder($company->id);
        // $areaSeeder->run();
    }

    /**
     * Handle the Company "updated" event.
     */
    public function updated(Company $company): void
    {
        
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "restored" event.
     */
    public function restored(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "force deleted" event.
     */
    public function forceDeleted(Company $company): void
    {
        //
    }
}
