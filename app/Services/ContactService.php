<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactAddress;
use App\Repositories\ContactRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContactService
{
    protected $contactRepo;
    protected $userRepo;

    public function __construct(ContactRepository $contactRepo, UserRepository $userRepo)
    {
        $this->contactRepo = $contactRepo;
        $this->userRepo = $userRepo;
    }

    public function getAllContacts($request)
    {
        return $this->contactRepo->getAllContacts($request);
    }

    public function createContact($request)
    {
        DB::beginTransaction();
        try {
            $authUser = $this->userRepo->findUserById(Auth::id());

            $contactData = [
                'organization_id' => $request->organization_id,
                'company_id' => $authUser->company_id,
                'name' => $request->name,
                'phone' => $request->phone,
                'profile_image' => $request->profile_image,
                'secondary_phone' => $request->secondary_phone,
                'email' => $request->email,
                'secondary_email' => $request->secondary_email,
                'whatsapp' => $request->whatsapp,
                'facebook' => $request->facebook,
                'linkedin' => $request->linkedin,
                'website' => $request->website,
                'dob' => $request->dob,
                'gender' => $request->gender,
                'marital_status' => $request->marital_status,
                'blood_group' => $request->blood_group,
                'religion' => $request->religion,
                'education' => $request->education,
                'profession' => $request->profession,
                'avalable_time' => $request->avalable_time,
                'bio' => $request->bio,
                'created_by' => $authUser->id,
            ];

            $contact = $this->contactRepo->createContact($contactData);

            // Handle addresses - store present and permanent addresses
            $this->storeContactAddresses($contact, $request);

            // If organization_id is provided and organization doesn't have a primary contact, set this contact as primary
            if ($request->organization_id) {
                $organization = \App\Models\Organization::find($request->organization_id);
                if ($organization && !$organization->primary_contact_id) {
                    $organization->update(['primary_contact_id' => $contact->id]);
                }
            }

            DB::commit();
            return ['success' => true, 'message' => 'Contact created successfully', 'contact' => $contact];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function storeContactAddresses(Contact $contact, $request)
    {
        $isSameAddress = $request->boolean('is_same_present_permanent', false);

        // Store permanent address
        if ($request->has('permanent_address')) {
            $permanentAddress = $request->permanent_address;
            ContactAddress::create([
                'contact_id' => $contact->id,
                'address_type' => 'permanent',
                'area_id' => $permanentAddress['area_id'] ?? null,
                'postal_code' => $permanentAddress['postal_code'] ?? null,
                'address' => $permanentAddress['address'] ?? null,
                'latitude' => $permanentAddress['latitude'] ?? null,
                'longitude' => $permanentAddress['longitude'] ?? null,
                'is_same_present_permanent' => $isSameAddress,
            ]);
        }

        // Store present address
        if ($request->has('present_address')) {
            $presentAddress = $request->present_address;
            ContactAddress::create([
                'contact_id' => $contact->id,
                'address_type' => 'present',
                'area_id' => $presentAddress['area_id'] ?? null,
                'postal_code' => $presentAddress['postal_code'] ?? null,
                'address' => $presentAddress['address'] ?? null,
                'latitude' => $presentAddress['latitude'] ?? null,
                'longitude' => $presentAddress['longitude'] ?? null,
                'is_same_present_permanent' => $isSameAddress,
            ]);
        } elseif ($isSameAddress && $request->has('permanent_address')) {
            // If same address, copy permanent address as present address
            $permanentAddress = $request->permanent_address;
            ContactAddress::create([
                'contact_id' => $contact->id,
                'address_type' => 'present',
                'area_id' => $permanentAddress['area_id'] ?? null,
                'postal_code' => $permanentAddress['postal_code'] ?? null,
                'address' => $permanentAddress['address'] ?? null,
                'latitude' => $permanentAddress['latitude'] ?? null,
                'longitude' => $permanentAddress['longitude'] ?? null,
                'is_same_present_permanent' => true,
            ]);
        }
    }

    public function show($uuid)
    {
        $contact = $this->contactRepo->findContactByUuid($uuid);

        if (!$contact) {
            throw new \Exception('Contact not found');
        }

        $contact->load(['organization', 'addresses.area', 'createdBy', 'updatedBy']);

        return $contact;
    }

    public function updateContact($uuid, $request)
    {
        DB::beginTransaction();
        try {
            $authUser = $this->userRepo->findUserById(Auth::id());
            $contact = $this->contactRepo->findContactByUuid($uuid);

            if (!$contact) {
                throw new \Exception('Contact not found');
            }

            $contactData = [
                'organization_id' => $request->organization_id,
                'name' => $request->name,
                'phone' => $request->phone,
                'profile_image' => $request->profile_image,
                'secondary_phone' => $request->secondary_phone,
                'email' => $request->email,
                'secondary_email' => $request->secondary_email,
                'whatsapp' => $request->whatsapp,
                'facebook' => $request->facebook,
                'linkedin' => $request->linkedin,
                'website' => $request->website,
                'dob' => $request->dob,
                'gender' => $request->gender,
                'marital_status' => $request->marital_status,
                'blood_group' => $request->blood_group,
                'religion' => $request->religion,
                'education' => $request->education,
                'profession' => $request->profession,
                'avalable_time' => $request->avalable_time,
                'bio' => $request->bio,
                'updated_by' => $authUser->id,
            ];

            $this->contactRepo->updateContact($contact, $contactData);

            // Update addresses
            $this->updateContactAddresses($contact, $request);

            // If organization_id is provided and organization doesn't have a primary contact, set this contact as primary
            if ($request->organization_id) {
                $organization = \App\Models\Organization::find($request->organization_id);
                if ($organization && !$organization->primary_contact_id) {
                    $organization->update(['primary_contact_id' => $contact->id]);
                }
            }

            DB::commit();
            return ['success' => true, 'message' => 'Contact updated successfully'];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function updateContactAddresses(Contact $contact, $request)
    {
        $isSameAddress = $request->boolean('is_same_present_permanent', false);

        // Update or create permanent address
        if ($request->has('permanent_address')) {
            $permanentAddress = $request->permanent_address;
            $permanentAddr = $contact->addresses()->where('address_type', 'permanent')->first();
            
            if ($permanentAddr) {
                $permanentAddr->update([
                    'area_id' => $permanentAddress['area_id'] ?? null,
                    'postal_code' => $permanentAddress['postal_code'] ?? null,
                    'address' => $permanentAddress['address'] ?? null,
                    'latitude' => $permanentAddress['latitude'] ?? null,
                    'longitude' => $permanentAddress['longitude'] ?? null,
                    'is_same_present_permanent' => $isSameAddress,
                ]);
            } else {
                ContactAddress::create([
                    'contact_id' => $contact->id,
                    'address_type' => 'permanent',
                    'area_id' => $permanentAddress['area_id'] ?? null,
                    'postal_code' => $permanentAddress['postal_code'] ?? null,
                    'address' => $permanentAddress['address'] ?? null,
                    'latitude' => $permanentAddress['latitude'] ?? null,
                    'longitude' => $permanentAddress['longitude'] ?? null,
                    'is_same_present_permanent' => $isSameAddress,
                ]);
            }
        }

        // Update or create present address
        if ($request->has('present_address')) {
            $presentAddress = $request->present_address;
            $presentAddr = $contact->addresses()->where('address_type', 'present')->first();
            
            if ($presentAddr) {
                $presentAddr->update([
                    'area_id' => $presentAddress['area_id'] ?? null,
                    'postal_code' => $presentAddress['postal_code'] ?? null,
                    'address' => $presentAddress['address'] ?? null,
                    'latitude' => $presentAddress['latitude'] ?? null,
                    'longitude' => $presentAddress['longitude'] ?? null,
                    'is_same_present_permanent' => $isSameAddress,
                ]);
            } else {
                ContactAddress::create([
                    'contact_id' => $contact->id,
                    'address_type' => 'present',
                    'area_id' => $presentAddress['area_id'] ?? null,
                    'postal_code' => $presentAddress['postal_code'] ?? null,
                    'address' => $presentAddress['address'] ?? null,
                    'latitude' => $presentAddress['latitude'] ?? null,
                    'longitude' => $presentAddress['longitude'] ?? null,
                    'is_same_present_permanent' => $isSameAddress,
                ]);
            }
        } elseif ($isSameAddress && $request->has('permanent_address')) {
            // If same address, update present address to match permanent
            $permanentAddress = $request->permanent_address;
            $presentAddr = $contact->addresses()->where('address_type', 'present')->first();
            
            if ($presentAddr) {
                $presentAddr->update([
                    'area_id' => $permanentAddress['area_id'] ?? null,
                    'postal_code' => $permanentAddress['postal_code'] ?? null,
                    'address' => $permanentAddress['address'] ?? null,
                    'latitude' => $permanentAddress['latitude'] ?? null,
                    'longitude' => $permanentAddress['longitude'] ?? null,
                    'is_same_present_permanent' => true,
                ]);
            } else {
                ContactAddress::create([
                    'contact_id' => $contact->id,
                    'address_type' => 'present',
                    'area_id' => $permanentAddress['area_id'] ?? null,
                    'postal_code' => $permanentAddress['postal_code'] ?? null,
                    'address' => $permanentAddress['address'] ?? null,
                    'latitude' => $permanentAddress['latitude'] ?? null,
                    'longitude' => $permanentAddress['longitude'] ?? null,
                    'is_same_present_permanent' => true,
                ]);
            }
        }
    }

    public function deleteContact($uuid)
    {
        DB::beginTransaction();
        try {
            $authUser = $this->userRepo->findUserById(Auth::id());
            $contact = $this->contactRepo->findContactByUuid($uuid);

            if (!$contact) {
                throw new \Exception('Contact not found');
            }

            $contact->update(['deleted_by' => $authUser->id]);
            $this->contactRepo->deleteContact($contact);

            DB::commit();
            return ['success' => true, 'message' => 'Contact deleted successfully'];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

