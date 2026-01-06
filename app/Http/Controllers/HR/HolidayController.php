<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->year ?? date('Y');
        
        $holidays = Holiday::where('company_id', Auth::user()->company_id ?? 1)
            ->whereYear('from_date', $year)
            ->orderBy('from_date', 'asc')
            ->get();
            
        return response()->json($holidays);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $holiday = Holiday::create([
            'company_id' => Auth::user()->company_id ?? 1,
            'name' => $request->name,
            'description' => $request->description,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'days_count' => ($request->days_count ?? 1),
            'type' => 'public', // default
        ]);

        return response()->json(['message' => 'Holiday added successfully', 'data' => $holiday]);
    }

    public function destroy($id)
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();
        return response()->json(['message' => 'Holiday deleted successfully']);
    }
}
