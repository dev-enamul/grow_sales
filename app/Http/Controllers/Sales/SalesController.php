<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\PaginatorTrait;

class SalesController extends Controller
{
    use PaginatorTrait;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $authUser = Auth::user();
            $companyId = $authUser->company_id;
            $keyword = $request->keyword;

            // Base query with relations
            $query = Sales::query()
                ->with([
                    'customer.primaryContact:id,uuid,name,phone,profile_image',
                    'salesBy:id,uuid,name',
                ])
                ->where('company_id', $companyId);

            // Keyword search
            if ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('id', 'like', "%{$keyword}%")
                        ->orWhereHas('customer.primaryContact', function ($contactQuery) use ($keyword) {
                            $contactQuery->where('name', 'like', "%{$keyword}%")
                                ->orWhere('phone', 'like', "%{$keyword}%")
                                ->orWhere('email', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('customer', function ($customerQuery) use ($keyword) {
                            $customerQuery->where('customer_code', 'like', "%{$keyword}%");
                        });
                });
            }

            // Sorting
            $sortBy = $request->input('sort_by');
            $sortOrder = strtolower($request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
            $allowedSorts = ['id', 'grand_total', 'paid', 'status', 'sale_date', 'created_at'];

            if ($sortBy && in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                // Default: latest first
                $query->orderBy('created_at', 'desc');
            }

            // Paginate
            $paginated = $this->paginateQuery($query, $request);

            // Map response
            $paginated['data'] = collect($paginated['data'])->map(function ($sale) {
                $contact = $sale->customer?->primaryContact;
                
                return [
                    'id' => $sale->id,
                    'uuid' => $sale->uuid,
                    'sales_id' => $sale->id, // Using id as sales_id
                    'sold_value' => $sale->grand_total ?? 0,
                    'paid_amount' => $sale->paid ?? 0,
                    'status' => $sale->status ?? 'pending',
                    'contact' => $contact ? [
                        'uuid' => $contact->uuid,
                        'name' => $contact->name,
                        'phone' => $contact->phone,
                        'profile_image_url' => getFileUrl($contact->profile_image),
                    ] : null,
                    'customer' => $sale->customer ? [
                        'id' => $sale->customer->id,
                        'uuid' => $sale->customer->uuid,
                        'customer_code' => $sale->customer->customer_code,
                    ] : null,
                    'sales_by' => $sale->salesBy ? [
                        'id' => $sale->salesBy->id,
                        'uuid' => $sale->salesBy->uuid,
                        'name' => $sale->salesBy->name,
                    ] : null,
                    'sale_date' => formatDate($sale->sale_date),
                    'delivery_date' => formatDate($sale->delivery_date),
                    'created_at' => formatDate($sale->created_at),
                ];
            });

            return success_response($paginated);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // TODO: Implement store method if needed
        return error_response('Not implemented', 501);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // TODO: Implement show method if needed
        return error_response('Not implemented', 501);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // TODO: Implement update method if needed
        return error_response('Not implemented', 501);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($uuid)
    {
        try {
            $authUser = Auth::user();
            $companyId = $authUser->company_id;

            $sale = Sales::where('uuid', $uuid)
                ->where('company_id', $companyId)
                ->first();

            if (!$sale) {
                return error_response('Sale not found', 404);
            }

            $sale->delete();

            return success_response(null, 'Sale deleted successfully');
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }
}

