<?php

namespace App\Http\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\ContactStoreRequest;
use App\Http\Requests\Contact\ContactUpdateRequest;
use App\Services\ContactService;
use Exception;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    protected $contactService;

    public function __construct(ContactService $contactService)
    {
        $this->contactService = $contactService;
    }

    public function index(Request $request)
    {
        try {
            return success_response($this->contactService->getAllContacts($request));
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function store(ContactStoreRequest $request)
    {
        try {
            $result = $this->contactService->createContact($request);
            $contact = $result['contact'] ?? null;
            $responseData = null;
            if ($contact) {
                $responseData = [
                    'id' => $contact->id,
                    'uuid' => $contact->uuid,
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
            $contact = $this->contactService->show($uuid);
            if (!$contact) {
                return error_response('Contact not found', 404);
            }

            $permanentAddress = $contact->addresses->where('address_type', 'permanent')->first();
            $presentAddress = $contact->addresses->where('address_type', 'present')->first();

            return success_response([
                'id' => $contact->id,
                'uuid' => $contact->uuid,
                'name' => $contact->name,
                'phone' => $contact->phone,
                'profile_image' => $contact->profile_image,
                'profile_image_url' => getFileUrl($contact->profile_image),
                'secondary_phone' => $contact->secondary_phone,
                'email' => $contact->email,
                'secondary_email' => $contact->secondary_email,
                'whatsapp' => $contact->whatsapp,
                'facebook' => $contact->facebook,
                'linkedin' => $contact->linkedin,
                'website' => $contact->website,
                'dob' => formatDate($contact->dob),
                'gender' => $contact->gender,
                'marital_status' => $contact->marital_status,
                'blood_group' => $contact->blood_group,
                'religion' => $contact->religion,
                'education' => $contact->education,
                'profession' => $contact->profession,
                'avalable_time' => $contact->avalable_time,
                'bio' => $contact->bio,
                'organization' => $contact->organization ? [
                    'id' => $contact->organization->id,
                    'uuid' => $contact->organization->uuid,
                    'name' => $contact->organization->name,
                ] : null,
                'organization_id' => $contact->organization_id,
                'permanent_address' => $permanentAddress ? [
                    'uuid' => $permanentAddress->uuid,
                    'area_id' => $permanentAddress->area_id,
                    'postal_code' => $permanentAddress->postal_code,
                    'address' => $permanentAddress->address,
                    'latitude' => $permanentAddress->latitude,
                    'longitude' => $permanentAddress->longitude,
                    'area' => $permanentAddress->area ? [
                        'id' => $permanentAddress->area->id,
                        'name' => $permanentAddress->area->name,
                    ] : null,
                ] : null,
                'present_address' => $presentAddress ? [
                    'uuid' => $presentAddress->uuid,
                    'area_id' => $presentAddress->area_id,
                    'postal_code' => $presentAddress->postal_code,
                    'address' => $presentAddress->address,
                    'latitude' => $presentAddress->latitude,
                    'longitude' => $presentAddress->longitude,
                    'area' => $presentAddress->area ? [
                        'id' => $presentAddress->area->id,
                        'name' => $presentAddress->area->name,
                    ] : null,
                ] : null,
                'is_same_present_permanent' => $permanentAddress ? $permanentAddress->is_same_present_permanent : false,
                'created_at' => formatDate($contact->created_at),
            ]);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function update(ContactUpdateRequest $request, $uuid)
    {
        try {
            $result = $this->contactService->updateContact($uuid, $request);
            return success_response(null, $result['message']);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function destroy($uuid)
    {
        try {
            $result = $this->contactService->deleteContact($uuid);
            return success_response(null, $result['message']);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }
}
