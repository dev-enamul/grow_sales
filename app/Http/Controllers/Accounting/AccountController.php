<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Traits\PaginatorTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    use PaginatorTrait;

    public function index(Request $request)
    {
        try {
            $keyword = $request->input('keyword');
            $selectOnly = $request->boolean('select');
            
            $query = Account::where('company_id', Auth::user()->company_id)
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', '%' . $keyword . '%')
                          ->orWhere('code', 'like', '%' . $keyword . '%');
                    });
                });

            if ($selectOnly) {
                // Return Bank and Asset account for payment receipt
                // Filter by type if needed (e.g. Asset)
                if ($request->has('type')) {
                    $query->where('type', $request->type);
                }

                $accounts = $query->select('id', 'name', 'code', 'type')
                    ->latest()
                    ->take(50)
                    ->get();
                return success_response($accounts);
            }

            $sortBy = $request->input('sort_by');
            $sortOrder = $request->input('sort_order', 'asc');

            $allowedSorts = ['name', 'code', 'type', 'created_at'];
            if ($sortBy && in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->latest();
            }

            $accounts = $this->paginateQuery(
                $query->select('uuid', 'name', 'code', 'type', 'is_bank_account', 'opening_balance', 'created_at'),
                $request
            );

            return success_response($accounts);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50', // code unique check in logic or migration handle
                'type' => 'required|in:Asset,Liability,Equity,Income,Expense',
            ]);

            // Check uniqueness manually for company scope if not handled well by simple unique rule
            $exists = Account::where('company_id', Auth::user()->company_id)
                ->where('code', $request->code)
                ->exists();
            
            if ($exists) {
                return error_response('Account code already exists for this company.', 422);
            }

            $account = Account::create([
                'name' => $request->input('name'),
                'code' => $request->input('code'),
                'type' => $request->input('type'),
                'opening_balance' => $request->input('opening_balance', 0),
                'company_id' => Auth::user()->company_id,
                'created_by' => Auth::id(),
            ]);

            return success_response([
                'id' => $account->id,
                'uuid' => $account->uuid,
            ], 'Account created successfully!', 201);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function show($uuid)
    {
        try {
            $account = Account::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$account) {
                return error_response('Account not found', 404);
            }

            return success_response($account);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function update(Request $request, $uuid)
    {
        try {
            $account = Account::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$account) {
                return error_response('Account not found', 404);
            }

            $request->validate([
                'name' => 'required|string|max:255',
                // Code update might be restricted or need check
                // 'code' => 'required|string', 
                'type' => 'required|in:Asset,Liability,Equity,Income,Expense',
            ]);
            
            // Check code uniqueness only if changed
            if ($request->code !== $account->code) {
                 $exists = Account::where('company_id', Auth::user()->company_id)
                    ->where('code', $request->code)
                    ->exists();
                if ($exists) {
                    return error_response('Account code already exists for this company.', 422);
                }
            }

            $account->update([
                'name' => $request->input('name'),
                'code' => $request->input('code'),
                'type' => $request->input('type'),
                'opening_balance' => $request->input('opening_balance', 0),
                'updated_by' => Auth::id(),
            ]);

            return success_response(null, 'Account updated successfully!');
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function destroy($uuid)
    {
        try {
            $account = Account::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$account) {
                return error_response('Account not found', 404);
            }
            
            // Should check if transactions exist before delete ideally

            $account->deleted_by = Auth::id();
            $account->save();
            $account->delete();

            return success_response(null, 'Account deleted successfully.');
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }
}
