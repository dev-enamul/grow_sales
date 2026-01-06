<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\Employee;
use Database\Seeders\AreaSeeder;
use Database\Seeders\AreaStructureSeeder;
use Database\Seeders\DesignationSeeder;
use Database\Seeders\LeadCategorySeeder;
use Database\Seeders\LeadSourceSeeder; 
use Illuminate\Support\Facades\Artisan;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        // Setup moved to updated event upon verification
    }

    /**
     * Handle the Company "updated" event.
     */
    public function updated(Company $company): void
    {
        if ($company->isDirty('is_verified') && $company->is_verified) {
             // 1. Lead Source
            $leadSourceSeeder = new LeadSourceSeeder($company->id);
            $leadSourceSeeder->run(); 

            // 2. Lead Category 
            $leadCategorySeeder = new LeadCategorySeeder($company->id);
            $leadCategorySeeder->run(); 

            // 3. Designation 
            $designationSeeder = new DesignationSeeder($company->id);
            $designationSeeder->run();
 

            // 5. Default Accounts
            $accountSeeder = new \Database\Seeders\AccountSeeder($company->id);
            $accountSeeder->run();

            // 6. Payment Reasons
            $paymentReasonSeeder = new \Database\Seeders\PaymentReasonSeeder($company->id);
            $paymentReasonSeeder->run();

            // 7. Measurement Units
            $measurmentUnitSeeder = new \Database\Seeders\MeasurmentUnitSeeder($company->id);
            $measurmentUnitSeeder->run();
            
            // 8. Product Units (New)
            $productUnitSeeder = new \Database\Seeders\ProductUnitSeeder($company->id);
            $productUnitSeeder->run();

            // 9. VAT Settings (New)
            $vatSettingSeeder = new \Database\Seeders\VatSettingSeeder($company->id);
            $vatSettingSeeder->run();

            // 10. Category Types (New)
            $categoryTypeSeeder = new \Database\Seeders\CategoryTypeSeeder($company->id);
            $categoryTypeSeeder->run();

            // 11. Leave Types
            $leaveTypeSeeder = new \Database\Seeders\LeaveTypeSeeder($company->id);
            $leaveTypeSeeder->run();

            // 12. Salary Components
            $salaryComponentSeeder = new \Database\Seeders\SalaryComponentSeeder($company->id);
            $salaryComponentSeeder->run();

            // 13. Work Shifts
            $workShiftSeeder = new \Database\Seeders\WorkShiftSeeder($company->id);
            $workShiftSeeder->run();
        }
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
