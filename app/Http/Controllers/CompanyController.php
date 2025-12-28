<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponse;

class CompanyController extends Controller
{ 
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        try {
            $companie = Company::find($user->company_id);
            return $this->successResponse($companie, 'Company retrieved successfully', 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'website' => 'nullable|url',
                'address' => 'nullable|string',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'primary_color' => 'nullable|string|max:20',
                'secondary_color' => 'nullable|string|max:20',
                'founded_date' => 'nullable|date',
            ]);

            if ($request->hasFile('logo')) {
                // Simplified file upload logic for now
                $file = $request->file('logo');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/company_logos'), $filename);
                $validated['logo'] = 'uploads/company_logos/' . $filename;
            }

            $company = Company::create($validated);
            return $this->successResponse($company, 'Company created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create company: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $company = Company::find($id);
            if (!$company) {
                return $this->errorResponse('Company not found', 404);
            }
            return $this->successResponse($company, 'Company retrieved successfully', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve company', 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $company = Company::find($id);
            if (!$company) {
                return $this->errorResponse('Company not found', 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'website' => 'nullable|url',
                'address' => 'nullable|string',
                'logo' => 'nullable', // Handle file or string
                'primary_color' => 'nullable|string|max:20',
                'secondary_color' => 'nullable|string|max:20',
                'founded_date' => 'nullable|date',
            ]);

            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/company_logos'), $filename);
                $validated['logo'] = 'uploads/company_logos/' . $filename;
            }

            $company->update($validated);
            return $this->successResponse($company, 'Company updated successfully', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update company: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $company = Company::find($id);
            if (!$company) {
                return $this->errorResponse('Company not found', 404);
            }
            $company->delete();
            return $this->successResponse(null, 'Company deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete company', 500);
        }
    }
}
