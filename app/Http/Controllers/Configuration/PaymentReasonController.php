<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\PaymentReason;
use App\Traits\PaginatorTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentReasonController extends Controller
{
    use PaginatorTrait;

    public function index(Request $request)
    {
        try {
            $keyword = $request->keyword;
            $selectOnly = $request->boolean('select');
            
            $query = PaymentReason::where('company_id', Auth::user()->company_id)
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('name', 'like', '%' . $keyword . '%')
                          ->orWhere('description', 'like', '%' . $keyword . '%');
                });

            if ($selectOnly) {
                $reasons = $query->select('id', 'name')
                    ->latest()
                    ->get(); // Usually list is small
                return success_response($reasons);
            }

            $sortBy = $request->input('sort_by');
            $sortOrder = $request->input('sort_order', 'asc');

            $allowedSorts = ['name', 'created_at'];
            if ($sortBy && in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->latest();
            }

            $reasons = $this->paginateQuery(
                $query->select('uuid', 'name', 'description', 'created_at'),
                $request
            );

            return success_response($reasons);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
            ]);
            
            $exists = PaymentReason::where('company_id', Auth::user()->company_id)
                ->where('name', $request->name)
                ->exists();
            
            if ($exists) {
                return error_response('Payment reason already exists.', 422);
            }

            $reason = PaymentReason::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'company_id' => Auth::user()->company_id,
                'created_by' => Auth::id(),
            ]);

            return success_response([
                'id' => $reason->id,
                'uuid' => $reason->uuid,
            ], 'Payment Reason created successfully!', 201);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function show($uuid)
    {
        try {
            $reason = PaymentReason::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$reason) {
                return error_response('Payment Reason not found', 404);
            }

            return success_response($reason);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function update(Request $request, $uuid)
    {
        try {
            $reason = PaymentReason::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$reason) {
                return error_response('Payment Reason not found', 404);
            }

            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $reason->update([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'updated_by' => Auth::id(),
            ]);

            return success_response(null, 'Payment Reason updated successfully!');
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function destroy($uuid)
    {
        try {
            $reason = PaymentReason::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$reason) {
                return error_response('Payment Reason not found', 404);
            }

            $reason->deleted_by = Auth::id();
            $reason->save();
            $reason->delete();

            return success_response(null, 'Payment Reason deleted successfully.');
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }
}
