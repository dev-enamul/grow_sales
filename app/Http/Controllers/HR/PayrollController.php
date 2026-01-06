<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\HrSetting;
use App\Models\LateDeductionSetting;
use App\Models\Payroll;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    /**
     * Generate Payroll for a Month
     */
    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
        ]);

        $companyId = Auth::user()->company_id ?? 1;
        $month = $request->month;
        $year = $request->year;

        // 1. Get All Employees
        $employees = User::where('company_id', $companyId)
             ->where('user_type', 'employee') // Assuming 'employee' type
             ->get();

        $generatedCount = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            // Delete existing draft payrolls for this month to allow re-generation
            Payroll::where('company_id', $companyId)
                ->where('month', $month)
                ->where('year', $year)
                ->where('status', 'draft')
                ->delete();

            foreach ($employees as $employee) {
                // Skip if payroll already processed/paid
                $exists = Payroll::where('user_id', $employee->id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->whereIn('status', ['processed', 'paid'])
                    ->exists();

                if ($exists) continue;

                $payrollData = $this->calculatePayroll($employee, $month, $year, $companyId);
                
                if ($payrollData) {
                    Payroll::create($payrollData);
                    $generatedCount++;
                }
            }

            DB::commit();
            
            return response()->json([
                'message' => "Payroll generated for $generatedCount employees.",
                'month' => "$month-$year"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to generate payroll: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Calculate individual employee payroll
     */
    private function calculatePayroll($employee, $month, $year, $companyId)
    {
        // 1. Get Salary Structure
        $structures = SalaryStructure::where('user_id', $employee->id)
            ->with('component')
            ->get();

        if ($structures->isEmpty()) {
            // Can't generate without structure
            return null;
        }

        // 2. Identify Basic Salary
        $basicComponent = $structures->first(function ($s) {
            return strtolower($s->component->name) === 'basic salary' || strtolower($s->component->name) === 'basic';
        });

        $basicSalary = $basicComponent ? $basicComponent->amount : 0;
        
        // 3. Calculate Earnings & Deductions
        $totalEarnings = 0;
        $totalDeductions = 0;

        foreach ($structures as $s) {
            $amount = 0;
            
            if ($s->component->calculation_type === 'percentage') {
                // Percentage of Basic
                $amount = ($basicSalary * $s->amount) / 100;
            } else {
                $amount = $s->amount;
            }

            if ($s->component->type === 'earning') {
                $totalEarnings += $amount;
            } else {
                $totalDeductions += $amount;
            }
        }

        // 4. Calculate Attendance Fines (Absent & Late)
        $attendanceData = $this->calculateAttendanceDeductions($employee, $month, $year, $companyId, $basicSalary);
        $attendanceFine = $attendanceData['total_deduction'];
        
        // 5. Final Net Pay
        // Note: Basic is usually part of Total Earnings in structure, so we don't add it twice.
        // Formula: Total Earnings - Total Deductions - Fines
        
        $netSalary = $totalEarnings - $totalDeductions - $attendanceFine;

        return [
            'company_id' => $companyId,
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $employee->id,
            'month' => $month,
            'year' => $year,
            'basic_salary' => $basicSalary,
            'total_allowances' => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'attendance_fine' => $attendanceFine,
            'overtime_amount' => 0, // TODO: Add OT calculation logic
            'bonus_amount' => 0,
            'loan_deduction' => 0, // TODO: Add Loan logic
            'net_salary' => $netSalary > 0 ? $netSalary : 0,
            'status' => 'draft',
            'created_by' => Auth::id(),
        ];
    }

    /**
     * Calculate Absent & Late Deductions
     */
    private function calculateAttendanceDeductions($employee, $month, $year, $companyId, $basicSalary)
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;
        
        // Per Day Salary (Based on 30 days usually or actual days)
        $perDaySalary = $basicSalary / 30; 

        // 1. Count Late Days
        $lateDays = Attendance::where('user_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_late', true)
            ->count();

        // 2. Count Absent Days
        // Logic: Total Days - Present/Leave/Holiday/Weekend
        // This is complex, simplified version:
        // Check HR Settings if we should deduct
        $settings = HrSetting::where('company_id', $companyId)->first();
        $deductAbsent = $settings ? $settings->salary_deduct_on_absent : true;
        
        $absentDeduction = 0;
        
        if ($deductAbsent) {
            // Build roster logic here ideally. For now, let's assume strict attendance.
            // Any day without 'present', 'leave', 'holiday' status is absent.
            $logs = Attendance::where('user_id', $employee->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();
                
            // Simple logic: If strict check enabled, find missing dates
            // ... (Omitted full calendar loop for brevity, assumed mapped via Attendances or manual 'absent' status) ...
            
            // Let's rely on 'absent' status in attendance table explicitly marked by cron/admin
            $absentCount = $logs->where('status', 'absent')->count(); 
            $absentDeduction = $absentCount * $perDaySalary;
        }

        // 3. Late Deduction Rule
        $lateDeduction = 0;
        
        // Get Rule for Employee Designation
        $lateRule = LateDeductionSetting::where('company_id', $companyId)
            ->where(function($q) use ($employee) {
                $q->where('designation_id', $employee->designation_id) // Assuming processed assigned
                  ->orWhereNull('designation_id');
            })
            ->orderBy('designation_id', 'desc') // Specific first
            ->first();

        if ($lateRule && $lateRule->is_active && $lateDays >= $lateRule->late_days_count) {
            $cycles = floor($lateDays / $lateRule->late_days_count);
            $deductionDays = $cycles * $lateRule->deduction_day_count;
            
            if ($lateRule->deduction_amount_type === 'fixed_amount') {
                $lateDeduction = $priorDeduction = $cycles * $lateRule->fixed_amount;
            } else {
                // Basic or Gross
                // Assuming Basic for safe side default
                $lateDeduction = $deductionDays * $perDaySalary;
            }
        }

        return [
            'total_deduction' => $absentDeduction + $lateDeduction,
            'details' => [
                'absent_amount' => $absentDeduction,
                'late_amount' => $lateDeduction
            ]
        ];
    }

    /**
     * List generated payrolls
     */
    public function index(Request $request)
    {
        $query = Payroll::with('user');
        
        if ($request->has('month')) $query->where('month', $request->month);
        if ($request->has('year')) $query->where('year', $request->year);
        
        return response()->json($query->paginate(20));
    }
}
