<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\OrganizationStoreRequest;
use App\Http\Requests\Organization\OrganizationUpdateRequest;
use App\Services\OrganizationService;
use App\Models\Lead;
use App\Models\Sales;
use Exception;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    protected $organizationService;

    public function __construct(OrganizationService $organizationService)
    {
        $this->organizationService = $organizationService;
    }

    public function index(Request $request)
    {
        try {
            return success_response($this->organizationService->getAllOrganizations($request));
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function store(OrganizationStoreRequest $request)
    {
        try {
            $result = $this->organizationService->createOrganization($request);
            $organization = $result['organization'] ?? null;
            $responseData = null;
            if ($organization) {
                $responseData = [
                    'id' => $organization->id,
                    'uuid' => $organization->uuid,
                ];
            }
            return success_response($responseData, $result['message']);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function show($uuid)
    {
        try {
            $organization = $this->organizationService->show($uuid);
            if (!$organization) {
                return error_response('Organization not found', 404);
            }

            // Get leads count for this organization
            $leadsCount = \App\Models\Lead::where('organization_id', $organization->id)
                ->where('company_id', auth()->user()->company_id)
                ->count();
            
            // Get sales count through leads
            $leadIds = \App\Models\Lead::where('organization_id', $organization->id)
                ->where('company_id', auth()->user()->company_id)
                ->pluck('id');
            
            $salesCount = \App\Models\Sales::whereIn('lead_id', $leadIds)
                ->where('company_id', auth()->user()->company_id)
                ->count();

            return success_response([
                'uuid' => $organization->uuid,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'organization_type' => $organization->organization_type,
                'industry' => $organization->industry,
                'website' => $organization->website,
                'address' => $organization->address,
                'billing_address' => $organization->billing_address,
                'shipping_address' => $organization->shipping_address,
                'description' => $organization->description,
                'logo' => $organization->logo,
                'logo_url' => getFileUrl($organization->logo),
                'founded_date' => formatDate($organization->founded_date),
                'registration_number' => $organization->registration_number,
                'tax_id' => $organization->tax_id,
                'is_active' => $organization->is_active,
                'primary_contact' => $organization->primaryContact ? [
                    'uuid' => $organization->primaryContact->uuid,
                    'name' => $organization->primaryContact->name,
                    'phone' => $organization->primaryContact->phone,
                    'email' => $organization->primaryContact->email,
                    'profile_image_url' => getFileUrl($organization->primaryContact->profile_image),
                ] : null,
                'contacts_count' => $organization->contacts->count(),
                'customers_count' => $organization->customers->count(),
                'leads_count' => $leadsCount,
                'sales_count' => $salesCount,
                'created_at' => formatDate($organization->created_at),
            ]);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function update(OrganizationUpdateRequest $request, $uuid)
    {
        try {
            $result = $this->organizationService->updateOrganization($uuid, $request);
            return success_response(null, $result['message']);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function destroy($uuid)
    {
        try {
            $result = $this->organizationService->deleteOrganization($uuid);
            return success_response(null, $result['message']);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }
}

