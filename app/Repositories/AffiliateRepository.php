<?php

namespace App\Repositories;

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
        $keyword = $request->keyword;

        $query = User::with([
                'reportingUsers' => function ($relation) {
                    $relation->where(function ($q) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>', now());
                    });
                },
            ])
            ->where('company_id', $authUser->company_id)
            ->where('user_type', 'affiliate');

        // Keyword search
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }

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

        // Sorting
        $sortBy = $request->input('sort_by');
        $sortOrder = strtolower($request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSorts = ['name', 'phone', 'created_at'];

        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest('created_at');
        }

        $paginated = $this->paginateQuery($query, $request);

        $paginated['data'] = collect($paginated['data'])->map(function ($user) {
            $activeReporting = $user->reportingUsers->first();
            $reportingUserName = null;
            if ($activeReporting) {
                $reportingUser = User::find($activeReporting->reporting_user_id);
                $reportingUserName = $reportingUser ? $reportingUser->name : null;
            }

            return [
                'uuid' => $user->uuid,
                'affiliate_id' => $user->user_id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'profile_image' => $user->profile_image,
                'profile_image_url' => getFileUrl($user->profile_image),
                'status' => $user->status,
                'referred_by' => $user->referred_by,
                'reporting_id' => $activeReporting ? $activeReporting->reporting_user_id : null,
                'reporting_user' => $reportingUserName,
                'created_at' => formatDate($user->created_at),
            ];
        })->values();

        return $paginated;
    }


}

