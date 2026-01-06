<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\WorkShift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * Get current attendance status for the user
     */
    public function status(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            return response()->json([
                'status' => 'not_checked_in',
                'can_check_in' => true,
                'can_check_out' => false,
                'shift' => $this->getUserShift($user),
            ]);
        }

        if ($attendance->clock_out) {
            return response()->json([
                'status' => 'checked_out',
                'can_check_in' => false,
                'can_check_out' => false,
                'data' => $attendance
            ]);
        }

        return response()->json([
            'status' => 'checked_in',
            'can_check_in' => false,
            'can_check_out' => true,
            'data' => $attendance
        ]);
    }

    /**
     * Clock In
     */
    public function checkIn(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now();

        // 1. Check if already checked in today
        $existing = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Already checked in for today.'], 400);
        }

        // 2. Determine Shift
        $shift = $this->getUserShift($user);
        
        // 3. Calculate Late Status
        $isLate = false;
        $lateReason = null;
        
        if ($shift) {
            $shiftStart = Carbon::parse($today->format('Y-m-d') . ' ' . $shift->start_time);
            $lateThreshold = $shiftStart->copy()->addMinutes($shift->late_tolerance_minutes);

            if ($now->gt($lateThreshold)) {
                $isLate = true;
                // $lateReason = $request->input('late_reason'); // Optional: capture reason from frontend
            }
        }

        // 4. Create Attendance Record
        $attendance = Attendance::create([
            'company_id' => $user->company_id ?? 1, // Fallback if no company_id on user yet
            'user_id' => $user->id,
            'shift_id' => $shift ? $shift->id : null,
            'date' => $today,
            'clock_in' => $now,
            'status' => $isLate ? 'late' : 'present', // Initial status
            'is_late' => $isLate,
            'late_reason' => $request->input('late_reason'), // Can pass reason if likely valid
            'ip_address' => $request->ip(),
            'location' => $request->input('location'),
        ]);

        return response()->json([
            'message' => 'Checked in successfully' . ($isLate ? ' (Late)' : ''),
            'data' => $attendance
        ]);
    }

    /**
     * Clock Out
     */
    public function checkOut(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now();

        // 1. Find active attendance
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNull('clock_out')
            ->first();

        if (!$attendance) {
            return response()->json(['message' => 'No active check-in found for today.'], 400);
        }

        // 2. Calculate Work Minutes
        $clockIn = Carbon::parse($attendance->clock_in);
        $workMinutes = $clockIn->diffInMinutes($now);

        // 3. Update Record
        $attendance->update([
            'clock_out' => $now,
            'work_minutes' => $workMinutes,
            // Overtime calculation can happen here or via a separate job/daily close process
            // For now simplest: if worked more than shift hours
        ]);

        // Basic Overtime Calc (Optional refinement later)
        if ($attendance->shift_id) {
            $shift = WorkShift::find($attendance->shift_id);
            if ($shift) {
                // Determine shift duration
                $shiftStart = Carbon::parse($today->format('Y-m-d') . ' ' . $shift->start_time);
                $shiftEnd = Carbon::parse($today->format('Y-m-d') . ' ' . $shift->end_time);
                if ($shiftEnd->lt($shiftStart)) {
                    $shiftEnd->addDay(); // Night shift crossing midnight
                }
                
                $shiftDurationMinutes = $shiftStart->diffInMinutes($shiftEnd);
                
                if ($workMinutes > $shiftDurationMinutes) {
                    $otMinutes = $workMinutes - $shiftDurationMinutes;
                    $attendance->update(['overtime_minutes' => $otMinutes]);
                }
            }
        }

        return response()->json([
            'message' => 'Checked out successfully',
            'data' => $attendance
        ]);
    }

    /**
     * Helper to get user shift
     */
    private function getUserShift($user)
    {
        // Logic: 
        // 1. Check if user has a specific roster (future feature: employee_schedules)
        // 2. Check if user has a shift_id assigned directly (if we add shift_id to users)
        // 3. Fallback to default General Shift for the company
        
        // For now, let's grab the first available shift for the company or 'General Shift'
        $companyId = $user->company_id ?? 1;
        
        // Priority 1: If user model needed a shift_id (not yet added, assumed General for now)
        
        // Priority 2: General Shift
        $shift = WorkShift::where('company_id', $companyId)
            ->where('name', 'General Shift')
            ->first();
            
        if (!$shift) {
            $shift = WorkShift::where('company_id', $companyId)->first();
        }

        return $shift;
    }
}
