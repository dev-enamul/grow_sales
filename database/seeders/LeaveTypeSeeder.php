<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
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

        if (LeaveType::where('company_id', $companyId)->exists()) {
            return;
        }

        $leaveTypes = [
            [
                'company_id' => $companyId,
                'name' => 'Casual Leave', // Fixed typo "Canual"
                'is_paid' => true,
                'days_allowed' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $companyId,
                'name' => 'Sick Leave',
                'is_paid' => true,
                'days_allowed' => 14,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $companyId,
                'name' => 'Earned Leave', // Often encashable
                'is_paid' => true,
                'days_allowed' => 0, // Depends on work days usually, initially 0
                'created_at' => now(),
                'updated_at' => now(),
            ],
             [
                'company_id' => $companyId,
                'name' => 'Unpaid Leave',
                'is_paid' => false,
                'days_allowed' => 365, // No strict limit, but cuts salary
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($leaveTypes as $type) {
            LeaveType::create($type);
        }
    }
}
