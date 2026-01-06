<?php

namespace Database\Seeders;

use App\Models\WorkShift;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WorkShiftSeeder extends Seeder
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
        // Assuming company_id 1 exists from previous seeders or registration
        $companyId = $this->companyId ?? 1; 

        // Check if shifts exist for this company to avoid duplicates on multiple runs
        if (WorkShift::where('company_id', $companyId)->exists()) {
            return;
        }

        $shifts = [
            [
                'company_id' => $companyId,
                'name' => 'General Shift',
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'late_tolerance_minutes' => 15,
                'weekend_days' => ["Friday"],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $companyId,
                'name' => 'Morning Shift',
                'start_time' => '07:00:00',
                'end_time' => '15:00:00',
                'late_tolerance_minutes' => 10,
                'weekend_days' => ["Friday"],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $companyId,
                'name' => 'Night Shift',
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
                'late_tolerance_minutes' => 20,
                'weekend_days' => ["Sunday"], // Example different weekend
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($shifts as $shift) {
            WorkShift::create($shift);
        }
    }
}
