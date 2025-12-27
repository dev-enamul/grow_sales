<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Traits\PaginatorTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankController extends Controller
{
    use PaginatorTrait;

    public function index(Request $request)
    {
        try {
            $keyword = $request->keyword;
            $selectOnly = $request->boolean('select');
            
            $query = Bank::where('company_id', Auth::user()->company_id)
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', '%' . $keyword . '%')
                          ->orWhere('account_number', 'like', '%' . $keyword . '%')
                          ->orWhere('branch', 'like', '%' . $keyword . '%');
                    });
                });

            if ($selectOnly) {
                $banks = $query->select('id', 'name', 'account_number')
                    ->latest()
                    ->take(10)
                    ->get();
                return success_response($banks);
            }

            $sortBy = $request->input('sort_by');
            $sortOrder = $request->input('sort_order', 'asc');

            $allowedSorts = ['name', 'account_number', 'account_holder', 'branch', 'created_at'];
            if ($sortBy && in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->latest();
            }

            $banks = $this->paginateQuery(
                $query->select('uuid', 'name', 'account_number', 'account_holder', 'branch', 'created_at'),
                $request
            );

            return success_response($banks);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'account_number' => 'required|string|max:255',
                'account_holder' => 'required|string|max:255',
                'branch' => 'required|string|max:255',
            ]);

            $bank = Bank::create([
                'name' => $request->input('name'),
                'account_number' => $request->input('account_number'),
                'account_holder' => $request->input('account_holder'),
                'branch' => $request->input('branch'),
                'company_id' => Auth::user()->company_id,
                'created_by' => Auth::id(),
            ]);

            // Auto create Account for this Bank
            \App\Models\Account::create([
                'company_id' => Auth::user()->company_id,
                'name' => $bank->name . ' - ' . $bank->account_number,
                'code' => 'BANK-' . $bank->id, // Temporary code generation strategy
                'type' => 'Asset',
                'is_bank_account' => true,
                'bank_id' => $bank->id,
                'created_by' => Auth::id(),
            ]);

            return success_response([
                'id' => $bank->id,
                'uuid' => $bank->uuid,
            ], 'Bank created successfully!', 201);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function show($uuid)
    {
        try {
            $bank = Bank::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$bank) {
                return error_response('Bank not found', 404);
            }

            return success_response([
                'uuid' => $bank->uuid,
                'name' => $bank->name,
                'account_number' => $bank->account_number,
                'account_holder' => $bank->account_holder,
                'branch' => $bank->branch,
                'created_at' => $bank->created_at,
            ]);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function update(Request $request, $uuid)
    {
        try {
            $bank = Bank::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$bank) {
                return error_response('Bank not found', 404);
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'account_number' => 'required|string|max:255',
                'account_holder' => 'required|string|max:255',
                'branch' => 'required|string|max:255',
            ]);

            $bank->update([
                'name' => $request->input('name'),
                'account_number' => $request->input('account_number'),
                'account_holder' => $request->input('account_holder'),
                'branch' => $request->input('branch'),
            ]);

            return success_response(null, 'Bank updated successfully!');
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function destroy($uuid)
    {
        try {
            $bank = Bank::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$bank) {
                return error_response('Bank not found', 404);
            }

            $bank->delete();

            return success_response(null, 'Bank deleted successfully.');
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }
}
