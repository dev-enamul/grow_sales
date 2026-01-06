<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Models\UserSalary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalaryStructureController extends Controller
{
    /**
     * Get All Salary Components
     */
    public function getComponents(Request $request)
    {
        $query = SalaryComponent::where('company_id', Auth::user()->company_id ?? 1);

        if (!$request->has('all')) {
            $query->where('is_active', true);
        }

        return response()->json($query->get());
    }
    public function getUserStructure($userId)
    {
        $activeSalary = UserSalary::where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$activeSalary) {
            return response()->json([]);
        }

        $structure = SalaryStructure::where('user_salary_id', $activeSalary->id)
            ->with('component')
            ->get();
            
        return response()->json($structure);
    }

    /**
     * Update/Create User Structure (Updates Active Salary)
     */
    public function updateStructure(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'components' => 'required|array',
            'components.*.salary_component_id' => 'required|exists:salary_components,id',
            'components.*.amount' => 'required|numeric|min:0',
        ]);

        $companyId = Auth::user()->company_id ?? 1;
        
        DB::beginTransaction();
        try {
            // Find Active Salary
            $activeSalary = UserSalary::where('user_id', $request->user_id)
                ->where('is_active', true)
                ->first();

            // If no active salary exists, create one (Fallback)
            if (!$activeSalary) {
                $gross = collect($request->components)->sum('amount');
                $activeSalary = UserSalary::create([
                    'company_id' => $companyId,
                    'user_id' => $request->user_id,
                    'gross_salary' => $gross,
                    'effective_date' => now(),
                    'is_active' => true,
                    'increment_reason' => 'Initial Setup',
                    'created_by' => Auth::id(),
                ]);
            } else {
                // Update Gross of active salary
                $gross = collect($request->components)->sum('amount');
                $activeSalary->gross_salary = $gross;
                $activeSalary->save();
            }

            // Sync Structure: Delete old for this salary log
            SalaryStructure::where('user_salary_id', $activeSalary->id)->delete();

            foreach ($request->components as $comp) {
                SalaryStructure::create([
                    'company_id' => $companyId,
                    'user_salary_id' => $activeSalary->id,
                    'component_id' => $comp['salary_component_id'], // Map input name to db column
                    'amount' => $comp['amount'],
                    'created_by' => Auth::id(),
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Salary structure updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update structure: ' . $e->getMessage()], 500);
        }
    }
    /**
     * Store a newly created salary component.
     */
    public function storeComponent(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:earning,deduction',
        ]);

        $slug = getSlug(SalaryComponent::class, $request->name);

        $component = SalaryComponent::create([
            'company_id' => Auth::user()->company_id ?? 1,
            'name' => $request->name,
            'slug' => $slug,
            'type' => $request->type,
            'is_active' => true,
        ]);

        return response()->json(['message' => 'Component created successfully', 'data' => $component]);
    }

    /**
     * Update the specified salary component.
     */
    public function updateComponent(Request $request, $id)
    {
        $component = SalaryComponent::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:earning,deduction',
            'is_active' => 'boolean'
        ]);

        if ($component->is_locked) {
            // Prevent changing name or type for locked components
            if ($request->name !== $component->name || $request->type !== $component->type) {
                 return response()->json(['message' => 'Cannot change Name or Type of system default component.'], 403);
            }
        }

        $slug = $component->slug;
        // Only generate new slug if name changed AND it's not locked (though locked block above handles it, good for safety)
        if (!$component->is_locked && $request->name !== $component->name) {
            $slug = getSlug(SalaryComponent::class, $request->name);
        }

        $component->update([
            'name' => $request->name,
            'slug' => $slug,
            'type' => $request->type,
            'is_active' => $request->input('is_active', $component->is_active),
        ]);

        return response()->json(['message' => 'Component updated successfully', 'data' => $component]);
    }

    /**
     * Remove the specified salary component.
     */
    public function destroyComponent($id)
    {
        $component = SalaryComponent::findOrFail($id);
        
        if ($component->is_locked) {
            return response()->json(['message' => 'Cannot delete system default component'], 403);
        }

        $component->delete();

        return response()->json(['message' => 'Component deleted successfully']);
    }
}
