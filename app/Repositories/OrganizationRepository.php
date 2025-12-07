<?php

namespace App\Repositories;

use App\Models\Organization;
use App\Traits\PaginatorTrait;
use Illuminate\Support\Facades\Auth;

class OrganizationRepository
{
    use PaginatorTrait;
    
    public function getAllOrganizations($request)
    {
        $authUser = Auth::user();
        $selectOnly = $request->boolean('select');
        $keyword = $request->keyword;

        $query = Organization::with([
                'primaryContact',
                'contacts',
                'customers',
            ]);

        // Keyword search
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('organization_type', 'like', "%{$keyword}%")
                    ->orWhere('industry', 'like', "%{$keyword}%")
                    ->orWhere('registration_number', 'like', "%{$keyword}%");
            });
        }

        if ($selectOnly) {
            return $query->select('id', 'uuid', 'name')
                ->orderBy('name')
                ->get()
                ->map(function ($organization) {
                    return [
                        'id' => $organization->id,
                        'uuid' => $organization->uuid,
                        'name' => $organization->name,
                    ];
                });
        }

        // Sorting
        $sortBy = $request->input('sort_by');
        $sortOrder = strtolower($request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSorts = ['name', 'organization_type', 'industry', 'created_at'];

        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest('created_at');
        }

        $paginated = $this->paginateQuery($query, $request);

        $paginated['data'] = collect($paginated['data'])->map(function ($organization) {
            return [
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
                ] : null,
                'contacts_count' => $organization->contacts->count(),
                'customers_count' => $organization->customers->count(),
                'created_at' => formatDate($organization->created_at),
            ];
        })->values();

        return $paginated;
    }

    public function createOrganization(array $data)
    {
        return Organization::create($data);
    }

    public function findOrganizationByUuid($uuid)
    {
        return Organization::where('uuid', $uuid)->first();
    }

    public function updateOrganization(Organization $organization, array $data)
    {
        $organization->update($data);
        return $organization;
    }

    public function deleteOrganization(Organization $organization)
    {
        $organization->delete();
        return $organization;
    }
}

