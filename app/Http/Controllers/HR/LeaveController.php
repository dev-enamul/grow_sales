<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeaveController extends Controller
{
    /**
     * Get Leave Types (Public/Employee)
     */
    public function getLeaveTypes()
    {
        $types = LeaveType::where('company_id', Auth::user()->company_id ?? 1)->get();
        return response()->json($types);
    }

    /**
     * Get Current Balance for User
     */
    public function myBalance()
    {
        $user = Auth::user();
        $year = Carbon::now()->year;

        // Ensure balances exist (Lazy create if not exists)
        $leaveTypes = LeaveType::where('company_id', $user->company_id ?? 1)->get();
        
        foreach ($leaveTypes as $type) {
            LeaveBalance::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'leave_type_id' => $type->id,
                    'year' => $year
                ],
                [
                    'company_id' => $user->company_id ?? 1,
                    'total_allowed' => $type->days_allowed,
                    'used' => 0,
                    'remaining' => $type->days_allowed
                ]
            );
        }

        $balances = LeaveBalance::where('user_id', $user->id)
            ->where('year', $year)
            ->with('leaveType')
            ->get();

        return response()->json($balances);
    }

    /**
     * Apply for Leave
     */
    public function apply(Request $request)
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
        ]);

        $user = Auth::user();
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $daysCount = $startDate->diffInDays($endDate) + 1; // Including start date

        // 1. Check Balance
        $balance = LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $request->leave_type_id)
            ->where('year', Carbon::now()->year)
            ->first();

        // If no strict balance check implemented yet, you can skip this block or warn
        if ($balance && $balance->remaining < $daysCount && $balance->leaveType->days_allowed > 0) {
            return response()->json(['message' => 'Insufficient leave balance!'], 400);
        }

        // 2. Create Application
        $application = LeaveApplication::create([
            'company_id' => $user->company_id ?? 1,
            'user_id' => $user->id,
            'leave_type_id' => $request->leave_type_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_count' => $daysCount,
            'reason' => $request->reason,
            'status' => 'pending'
        ]);

        return response()->json(['message' => 'Leave application submitted successfully.', 'data' => $application]);
    }

    /**
     * List Applications (Admin/Approver)
     */
    public function index(Request $request) 
    {
        // Filter by Status
        $query = LeaveApplication::with(['user', 'leaveType']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Standard Admin checks usually
        if (!Auth::user()->can('leave.view_all')) {
            $query->where('user_id', Auth::user()->id);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate(20));
    }

    /**
     * Approve/Reject Leave
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        $application = LeaveApplication::findOrFail($id);
        
        if ($application->status !== 'pending') {
            return response()->json(['message' => 'Application is already processed.'], 400);
        }

        DB::beginTransaction();

        try {
            $application->status = $request->status;
            $application->approved_by = Auth::id();
            
            if ($request->status === 'rejected') {
                $application->rejection_reason = $request->rejection_reason;
            }

            if ($request->status === 'approved') {
                // Deduct Balance
                $balance = LeaveBalance::where('user_id', $application->user_id)
                    ->where('leave_type_id', $application->leave_type_id)
                    ->where('year', Carbon::now()->year)
                    ->first();

                if ($balance) {
                    $balance->used += $application->days_count;
                    $balance->remaining -= $application->days_count;
                    $balance->save();
                }
            }

            $application->save();
            DB::commit();

            return response()->json(['message' => 'Leave status updated successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Something went wrong.'], 500);
        }
    }
}
