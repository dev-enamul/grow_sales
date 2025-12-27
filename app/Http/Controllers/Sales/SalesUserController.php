<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class SalesUserController extends Controller
{
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $salesUser = SalesUser::findOrFail($id);

            $request->validate([
                'commission_type' => 'required|in:percentage,amount',
                'commission_value' => 'required|numeric|min:0',
                'commission' => 'required|numeric|min:0',
            ]);

            $salesUser->update([
                'commission_type' => $request->commission_type,
                'commission_value' => $request->commission_value,
                'commission' => $request->commission,
            ]);

            DB::commit();
            return success_response($salesUser, 'Commission updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return error_response($e->getMessage(), 500);
        }
    }

    public function bulkUpdate(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'sales_uuid' => 'required|exists:sales,uuid',
                'users' => 'array',
                'users.*.user_id' => 'required|exists:users,id',
                'users.*.commission_type' => 'required|in:percentage,amount',
                'users.*.commission_value' => 'required|numeric|min:0',
                'users.*.commission' => 'required|numeric|min:0',
            ]);

            $sale = \App\Models\Sales::where('uuid', $request->sales_uuid)->firstOrFail();

            // Sync means remove old and add new. 
            // We can delete all existing for this sale and recreate.
            // Or we can try to update existing if ID provided, but deleting and recreating is simpler for "sync".
            SalesUser::where('sales_id', $sale->id)->forceDelete(); // forceDelete or delete based on preference. Soft delete is safer but might pile up. Default is fine (SoftDelete if trait used).

            foreach ($request->users as $userData) {
                SalesUser::create([
                    'sales_id' => $sale->id,
                    'user_id' => $userData['user_id'],
                    'commission_type' => $userData['commission_type'],
                    'commission_value' => $userData['commission_value'],
                    'commission' => $userData['commission'],
                    'payable_commission' => $userData['payable_commission'] ?? $userData['commission'],
                    'paid_commission' => $userData['paid_commission'] ?? 0, // Keep existing paid? Frontend needs to send it back if we want to preserve it. Usually PAID amount shouldn't be wiped easily.
                    // Important: If we delete and recreate, we lose "paid_commission" if javascript doesn't send it back.
                    // Frontend SHOULD send back current state.
                    'created_by' => auth()->id(),
                ]);
            }

            DB::commit();
            return success_response(null, 'Commissions updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return error_response($e->getMessage(), 500);
        }
    }
}
