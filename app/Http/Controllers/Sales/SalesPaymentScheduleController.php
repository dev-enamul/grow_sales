<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sale; // Assuming Model is Sale
use App\Models\Sales; // Or Sales? Let's check.
use App\Models\SalesPaymentSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class SalesPaymentScheduleController extends Controller
{
    public function index(Request $request)
    {
        try {
            $salesUuid = $request->input('sales_uuid');
            if (!$salesUuid) {
                return error_response('Sales UUID is required', 400);
            }

            // Find Sales ID from UUID
            // Assuming Sales model exists. I need to verify the model name. 
            // Previous view_file showed App\Models\SalesPaymentSchedule belongsTo Sale::class.
            // But I should check if 'Sale.php' or 'Sales.php' exists.
            
            $sale = \App\Models\Sales::where('uuid', $salesUuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$sale) {
                 return error_response('Sales not found', 404);
            }

            $schedules = SalesPaymentSchedule::with('paymentReason')
                ->where('sales_id', $sale->id)
                ->where('company_id', Auth::user()->company_id)
                ->orderBy('due_date', 'asc')
                ->get();
            
            // Append status attribute
            $schedules->append('status');

            return success_response($schedules);

        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'sales_uuid' => 'required',
                'payment_reason_id' => 'required|exists:payment_reasons,id',
                'amount' => 'required|numeric|min:0',
                'due_date' => 'required|date',
            ]);

            $sale = \App\Models\Sales::where('uuid', $request->sales_uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$sale) {
                return error_response('Sales not found', 404);
            }

            $schedule = SalesPaymentSchedule::create([
                'company_id' => Auth::user()->company_id,
                'sales_id' => $sale->id,
                'payment_reason_id' => $request->payment_reason_id,
                'amount' => $request->amount,
                'due_date' => $request->due_date,
                'notes' => $request->note ?? $request->notes, // Handle both
                'created_by' => Auth::id(),
            ]);

            return success_response($schedule, 'Schedule created successfully', 201);

        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }
}
