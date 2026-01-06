<?php

namespace Database\Seeders;

use App\Models\SalaryComponent;
use Illuminate\Database\Seeder;

class SalaryComponentSeeder extends Seeder
{
    protected $companyId;

    public function __construct($companyId = null)
    {
        $this->companyId = $companyId;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyId = $this->companyId ?? 1;

        if (SalaryComponent::where('company_id', $companyId)->exists()) {
            return;
        }

        $components = [
            // Earnings
            [
                'company_id' => $companyId,
                'name' => 'Basic Salary',
                'slug' => 'basic',
                'type' => 'earning',
                'calculation_type' => 'fixed',
                'is_active' => true,
                'is_locked' => true,
            ],
            [
                'company_id' => $companyId,
                'name' => 'House Rent Allowance',
                'slug' => 'house_rent',
                'type' => 'earning',
                'calculation_type' => 'percentage', // e.g., 50% of Basic
                'is_active' => true,
                'is_locked' => true,
            ],
            [
                'company_id' => $companyId,
                'name' => 'Medical Allowance',
                'slug' => 'medical',
                'type' => 'earning',
                'calculation_type' => 'fixed',
                'is_active' => true,
                'is_locked' => true,
            ],
             [
                'company_id' => $companyId,
                'name' => 'Conveyance Allowance',
                'slug' => 'conveyance',
                'type' => 'earning',
                'calculation_type' => 'fixed',
                'is_active' => true,
                'is_locked' => true,
            ],
            
            // Deductions

        ];

        foreach ($components as $component) {
            SalaryComponent::create($component);
        }
    }
}
