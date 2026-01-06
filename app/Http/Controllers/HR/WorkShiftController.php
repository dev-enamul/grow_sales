<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\WorkShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkShiftController extends Controller
{
    /**
     * Get All Work Shifts
     */
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id ?? 1;
        
        $query = WorkShift::where('company_id', $companyId);

        // Search
        if ($request->has('keyword') && $request->keyword) {
            $query->where('name', 'like', '%' . $request->keyword . '%');
        }

        // Sorting
        if ($request->has('sort_by') && $request->has('sort_order')) {
            $sortBy = $request->sort_by;
            $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        if ($request->has('per_page')) {
            $perPage = $request->per_page;
            $shifts = $query->paginate($perPage);
            
            return response()->json([
                'data' => $shifts->items(),
                'meta' => [
                    'current_page' => $shifts->currentPage(),
                    'per_page' => $shifts->perPage(),
                    'total' => $shifts->total(),
                    'last_page' => $shifts->lastPage(),
                ]
            ]);
        }

        $shifts = $query->get();
        return response()->json($shifts);
    }

    /**
     * Create Work Shift
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        $maxGrace = $request->late_tolerance_minutes ?? 15;

        $shift = WorkShift::create([
            'company_id' => Auth::user()->company_id ?? 1,
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'late_tolerance_minutes' => $maxGrace,
            'weekend_days' => $request->weekend_days ?? null,
        ]);

        return response()->json(['message' => 'Shift created successfully', 'data' => $shift]);
    }

    /**
     * Update Work Shift
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        $shift = WorkShift::findOrFail($id);
        $shift->update($request->all());

        return response()->json(['message' => 'Shift updated successfully', 'data' => $shift]);
    }

    /**
     * Delete Work Shift
     */
    public function destroy($id)
    {
        $shift = WorkShift::findOrFail($id);
        // Optionally check if assigned to users
        $shift->delete();
        return response()->json(['message' => 'Shift deleted successfully']);
    }
}
