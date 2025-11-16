<?php

namespace App\Repositories;

use App\Models\Affiliate;
use App\Models\User;
use App\Traits\PaginatorTrait;
use Illuminate\Support\Facades\Auth;

class AffiliateRepository
{
    use PaginatorTrait;
    public function getAllAffiliates($request)
    {
        $authUser = Auth::user();
        $selectOnly = $request->boolean('select');

        $query = User::with('affiliate')
            ->where('company_id', $authUser->company_id)
            ->where('user_type', 'affiliate');

        if ($selectOnly) {
            return $query->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                    ];
                });
        }

        $paginated = $this->paginateQuery($query, $request);

        $paginated['data'] = collect($paginated['data'])->map(function ($user) {
            return [
                'uuid' => $user->uuid,
                'affiliate_id' => $user->affiliate->affiliate_id ?? null,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'profile_image' => $user->profile_image,
                'profile_image_url' => getFileUrl($user->profile_image),
                'status' => $user->affiliate->status ?? null,
                'referred_by' => $user->affiliate->referred_by,
            ];
        })->values();

        return $paginated;
    }

    public function createAffiliate(array $data)
    {
        return Affiliate::create($data);
    }

}

