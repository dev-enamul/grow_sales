<?php

namespace App\Repositories;

use App\Models\Contact;
use App\Traits\PaginatorTrait;
use Illuminate\Support\Facades\Auth;

class ContactRepository
{
    use PaginatorTrait;
    
    public function getAllContacts($request)
    {
        $authUser = Auth::user();
        $selectOnly = $request->boolean('select');
        $keyword = $request->keyword;

        $query = Contact::with([
                'organization',
                'addresses',
                'createdBy',
                'updatedBy',
            ])
            ->where('company_id', $authUser->company_id);

        // Keyword search
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('secondary_phone', 'like', "%{$keyword}%")
                    ->orWhere('secondary_email', 'like', "%{$keyword}%")
                    ->orWhereHas('organization', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
            });
        }

        if ($selectOnly) {
            return $query->select('id', 'uuid', 'name', 'phone', 'email')
                ->orderBy('name')
                ->get()
                ->map(function ($contact) {
                    return [
                        'id' => $contact->id, 
                        'name' => $contact->name,
                    ];
                });
        }

        // Sorting
        $sortBy = $request->input('sort_by');
        $sortOrder = strtolower($request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSorts = ['name', 'phone', 'email', 'created_at'];

        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest('created_at');
        }

        $paginated = $this->paginateQuery($query, $request);

        $paginated['data'] = collect($paginated['data'])->map(function ($contact) {
            return [
                'id' => $contact->id,
                'uuid' => $contact->uuid,
                'name' => $contact->name,
                'phone' => $contact->phone,
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
                    'uuid' => $contact->organization->uuid,
                    'name' => $contact->organization->name,
                ] : null,
                'addresses' => $contact->addresses->map(function ($address) {
                    return [
                        'uuid' => $address->uuid,
                        'address_type' => $address->address_type,
                        'address' => $address->address,
                        'postal_code' => $address->postal_code,
                        'area_id' => $address->area_id,
                        'latitude' => $address->latitude,
                        'longitude' => $address->longitude,
                        'is_same_present_permanent' => $address->is_same_present_permanent,
                    ];
                }),
                'created_at' => formatDate($contact->created_at),
            ];
        })->values();

        return $paginated;
    }

    public function createContact(array $data)
    {
        return Contact::create($data);
    }

    public function findContactByUuid($uuid)
    {
        $authUser = Auth::user();
        return Contact::where('uuid', $uuid)
            ->where('company_id', $authUser->company_id)
            ->first();
    }

    public function updateContact(Contact $contact, array $data)
    {
        $contact->update($data);
        return $contact;
    }

    public function deleteContact(Contact $contact)
    {
        $contact->delete();
        return $contact;
    }
}

