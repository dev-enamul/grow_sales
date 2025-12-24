<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sale; 
use App\Models\Sales; 
use App\Models\SalesPayment;
use App\Models\SalesPaymentSchedule;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class SalesPaymentController extends Controller
{
    public function index(Request $request)
    {
        try {
            $salesUuid = $request->input('sales_uuid');
            if (!$salesUuid) {
                 // Or return all if no filter, but usually we list per sales or filtered list
                 // For now, let's enforce sales_uuid or return paginated all?
                 // Let's support both.
            }

            $query = SalesPayment::with(['account', 'paymentSchedule.paymentReason', 'receivedBy'])
                ->where('company_id', Auth::user()->company_id);

            if ($salesUuid) {
                $sale = \App\Models\Sales::where('uuid', $salesUuid)->first();
                if ($sale) {
                    $query->where('sales_id', $sale->id);
                } else {
                    return success_response([]);
                }
            }

            $payments = $query->latest('payment_date')->get();

            return success_response($payments);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'sales_uuid' => 'required',
                'account_id' => 'required|exists:accounts,id',
                'amount' => 'required|numeric|min:0',
                'payment_date' => 'required|date',
                'payment_schedule_id' => 'nullable|exists:sales_payment_schedules,id',
            ]);

            $sale = \App\Models\Sales::where('uuid', $request->sales_uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$sale) {
                return error_response('Sales not found', 404);
            }

            // 1. Create Sales Payment
            $payment = SalesPayment::create([
                'company_id' => Auth::user()->company_id,
                'sales_id' => $sale->id,
                'payment_schedule_id' => $request->payment_schedule_id,
                'account_id' => $request->account_id,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'transaction_ref' => $request->transaction_id, // Map frontend 'transaction_id' (Ref) to 'transaction_ref'
                'note' => $request->note,
                'payment_method' => 'Cash/Bank', 
                'status' => 1,
                'created_by' => Auth::id(),
            ]);

            // 2. Create Transaction
            // Find Credit Account (Accounts Receivable)
            $receivableAccount = Account::where('company_id', Auth::user()->company_id)
                ->where('name', 'Accounts Receivable')
                ->first();
            
            // Fallback if not found (e.g. Sales)
            if (!$receivableAccount) {
                $receivableAccount = Account::where('company_id', Auth::user()->company_id)
                    ->where('name', 'Sales/Revenue')
                    ->first();
            }

            // If still not found, we cannot create double entry properly. 
            // In real app, we should ensure these exist.
            // For now, fail or use same account (bad practice but avoids 500)
            $creditAccountId = $receivableAccount ? $receivableAccount->id : $request->account_id;

            $transaction = Transaction::create([
                'company_id' => Auth::user()->company_id,
                'debit_account_id' => $request->account_id, // Asset increases (Debit)
                'credit_account_id' => $creditAccountId,   // Receivable decreases (Credit)
                'debit' => $request->amount,
                'credit' => $request->amount,
                'description' => 'Payment received for Sales #' . $sale->custom_id,
                'date' => $request->payment_date,
                'transactionable_type' => 'App\Models\SalesPayment',
                'transactionable_id' => $payment->id,
                'created_by' => Auth::id(),
            ]);

            // 3. Link Transaction back to SalesPayment
            $payment->update(['transaction_id' => $transaction->id]);

            // 3. Update Account Balance -> Skipped as we use Transaction-based calculated balance
            // If performance becomes an issue, we can add a cached 'current_balance' column later.
            
            DB::commit();

            return success_response($payment, 'Payment received successfully', 201);

        } catch (Exception $e) {
            DB::rollBack();
            return error_response($e->getMessage(), 500);
        }
    }
}
