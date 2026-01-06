<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HrSetting;
use App\Models\LateDeductionSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HrSettingController extends Controller
{
    /**
     * Get All HR Settings
     */
    public function getSettings()
    {
        $companyId = Auth::user()->company_id ?? 1;

        // General Policy
        $general = HrSetting::firstOrCreate(
            ['company_id' => $companyId],
            [
                'weekend_type' => 'shift_based',
                'salary_deduct_on_absent' => true,
                'absent_fine_type' => 'per_day',
            ]
        );

        // Late Deduction Rules
        $lateRules = LateDeductionSetting::where('company_id', $companyId)
            ->with('designation')
            ->get();

        return response()->json([
            'general' => $general,
            'late_rules' => $lateRules
        ]);
    }

    /**
     * Update General Settings
     */
    public function updateGeneral(Request $request)
    {
        $request->validate([
            'weekend_type' => 'required|in:shift_based,employee_based',
            'absent_fine_type' => 'required|in:fixed,per_day',
        ]);

        $companyId = Auth::user()->company_id ?? 1;

        $setting = HrSetting::updateOrCreate(
            ['company_id' => $companyId],
            $request->all()
        );

        return response()->json(['message' => 'Settings updated successfully', 'data' => $setting]);
    }

    /**
     * Add/Update Late Deduction Rule
     */
    public function saveLateRule(Request $request)
    {
        $request->validate([
            'late_days_count' => 'required|integer|min:1',
            'deduction_day_count' => 'required|numeric|min:0.5',
            'deduction_amount_type' => 'required|in:basic_salary,gross_salary,fixed_amount',
        ]);

        $companyId = Auth::user()->company_id ?? 1;

        // If updating existing rule
        if ($request->id) {
            $rule = LateDeductionSetting::findOrFail($request->id);
            $rule->update($request->all());
        } else {
            // New Rule
            $rule = LateDeductionSetting::create([
                'company_id' => $companyId,
                'designation_id' => $request->designation_id, // Null means Global
                'late_days_count' => $request->late_days_count,
                'deduction_day_count' => $request->deduction_day_count,
                'deduction_amount_type' => $request->deduction_amount_type,
                'fixed_amount' => $request->fixed_amount,
            ]);
        }

        return response()->json(['message' => 'Late deduction rule saved', 'data' => $rule]);
    }

    /**
     * Delete Rule
     */
    public function deleteLateRule($id)
    {
        $rule = LateDeductionSetting::where('id', $id)->firstOrFail();
        $rule->delete();
        return response()->json(['message' => 'Rule deleted successfully']);
    }
}
