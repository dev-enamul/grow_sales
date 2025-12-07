<?php

namespace App\Services;

use App\Models\Organization;
use App\Repositories\OrganizationRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrganizationService
{
    protected $organizationRepo;
    protected $userRepo;

    public function __construct(OrganizationRepository $organizationRepo, UserRepository $userRepo)
    {
        $this->organizationRepo = $organizationRepo;
        $this->userRepo = $userRepo;
    }

    public function getAllOrganizations($request)
    {
        return $this->organizationRepo->getAllOrganizations($request);
    }

    public function createOrganization($request)
    {
        DB::beginTransaction();
        try {
            $authUser = $this->userRepo->findUserById(Auth::id());

            $organizationData = [
                'name' => $request->name,
                'organization_type' => $request->organization_type,
                'industry' => $request->industry,
                'website' => $request->website,
                'address' => $request->address,
                'billing_address' => $request->billing_address,
                'shipping_address' => $request->shipping_address,
                'description' => $request->description,
                'logo' => $request->logo,
                'founded_date' => $request->founded_date,
                'registration_number' => $request->registration_number,
                'tax_id' => $request->tax_id,
                'is_active' => true,
                'primary_contact_id' => $request->primary_contact_id,
                'created_by' => $authUser->id,
            ];

            $organization = $this->organizationRepo->createOrganization($organizationData);

            DB::commit();
            return ['success' => true, 'message' => 'Organization created successfully', 'organization' => $organization];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show($uuid)
    {
        $organization = $this->organizationRepo->findOrganizationByUuid($uuid);

        if (!$organization) {
            throw new \Exception('Organization not found');
        }

        $organization->load(['primaryContact', 'contacts', 'customers', 'createdBy', 'updatedBy']);

        return $organization;
    }

    public function updateOrganization($uuid, $request)
    {
        DB::beginTransaction();
        try {
            $authUser = $this->userRepo->findUserById(Auth::id());
            $organization = $this->organizationRepo->findOrganizationByUuid($uuid);

            if (!$organization) {
                throw new \Exception('Organization not found');
            }

            $organizationData = [
                'name' => $request->name,
                'organization_type' => $request->organization_type,
                'industry' => $request->industry,
                'website' => $request->website,
                'address' => $request->address,
                'billing_address' => $request->billing_address,
                'shipping_address' => $request->shipping_address,
                'description' => $request->description,
                'founded_date' => $request->founded_date,
                'registration_number' => $request->registration_number,
                'tax_id' => $request->tax_id,
                'is_active' => true,
                'primary_contact_id' => $request->primary_contact_id,
                'updated_by' => $authUser->id,
            ];

            if ($request->has('logo')) {
                $organizationData['logo'] = $request->logo;
            }

            $this->organizationRepo->updateOrganization($organization, $organizationData);

            DB::commit();
            return ['success' => true, 'message' => 'Organization updated successfully'];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteOrganization($uuid)
    {
        DB::beginTransaction();
        try {
            $authUser = $this->userRepo->findUserById(Auth::id());
            $organization = $this->organizationRepo->findOrganizationByUuid($uuid);

            if (!$organization) {
                throw new \Exception('Organization not found');
            }

            $organization->update(['deleted_by' => $authUser->id]);
            $this->organizationRepo->deleteOrganization($organization);

            DB::commit();
            return ['success' => true, 'message' => 'Organization deleted successfully'];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

