<?php

namespace App\Services;

use App\Helpers\ReportingService;
use App\Models\User;
use App\Repositories\AffiliateRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AffiliateService
{
    protected $affiliateRepo;
    protected $userRepo;

    public function __construct(AffiliateRepository $affiliateRepo, UserRepository $userRepo)
    {
        $this->affiliateRepo = $affiliateRepo;
        $this->userRepo = $userRepo;
    }

    public function getAllAffiliates($request)
    {
        return $this->affiliateRepo->getAllAffiliates($request);
    }

    public function createAffiliate($request)
    {
        DB::beginTransaction();
        try {
            $authUser = $this->userRepo->findUserById(Auth::id());

            $this->ensureUniqueEmailWithinCompany($request->email, $authUser->company_id);

            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make('123456'),
                'user_type' => 'affiliate',
                'company_id' => $authUser->company_id,
                'created_by' => $authUser->id,
            ];

            if ($request->has('profile_image')) {
                $userData['profile_image'] = $request->profile_image;
            }

            $user = $this->userRepo->createUser($userData);

            // Set affiliate fields on user
            $user->user_id = User::generateNextAffiliateId();
            $user->referred_by = $request->referred_by;
            $user->status = 1; // Active by default
            $user->save();

            if ($request->reporting_id) {
                $reportingUser = User::find($request->reporting_id);
                if ($reportingUser) {
                    $this->userRepo->createUserReporting([
                        'user_id' => $user->id,
                        'reporting_user_id' => $reportingUser->id,
                        'start_date' => now(),
                        'created_by' => $authUser->id,
                    ]);

                    $this->updateReportingHierarchy($user);
                    $this->updateReportingHierarchy($reportingUser);
                }
            }

            DB::commit();
            return ['success' => true, 'message' => 'Affiliate created successfully', 'user' => $user];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show($uuid)
    {
        return $this->userRepo->findUserByUuId($uuid);
    }

    public function updateAffiliate($uuid, $request)
    {
        DB::beginTransaction();
        try {
            $authUser = $this->userRepo->findUserById(Auth::id());
            $user = $this->userRepo->findUserByUuId($uuid);

            if (!$user || $user->company_id !== $authUser->company_id || !$user->isAffiliate()) {
                throw new \Exception('Affiliate not found');
            }

            if ($user->email !== $request->email) {
                $this->ensureUniqueEmailWithinCompany($request->email, $authUser->company_id, $user->id);
            }

            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'updated_by' => $authUser->id,
            ];

            if ($request->has('profile_image')) {
                $userData['profile_image'] = $request->profile_image;
            }

            $user->update($userData);

            // Update affiliate fields
            if ($request->has('referred_by')) {
                $user->referred_by = $request->referred_by;
            }
            if ($request->has('status')) {
                $user->status = $request->input('status', $user->status);
            }
            $user->updated_by = $authUser->id;
            $user->save();

            $currentReportingUser = $user->reportingUsers()
                ->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', now());
                })
                ->first();

            $currentReportingUserId = $currentReportingUser ? $currentReportingUser->reporting_user_id : null;
            $newReportingUserId = $request->reporting_id ?? null;

            if ($currentReportingUserId != $newReportingUserId) {
                $this->updateAffiliateReporting($user, $newReportingUserId, $authUser);
            }

            DB::commit();
            return ['success' => true, 'message' => 'Affiliate updated successfully'];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteAffiliate($uuid)
    {
        DB::beginTransaction();
        try {
            $authUser = $this->userRepo->findUserById(Auth::id());
            $user = $this->userRepo->findUserByUuId($uuid);

            if (!$user || $user->company_id !== $authUser->company_id || !$user->isAffiliate()) {
                throw new \Exception('Affiliate not found');
            }

            $user->update(['deleted_by' => $authUser->id]);
            $user->delete();

            DB::commit();
            return ['success' => true, 'message' => 'Affiliate deleted successfully'];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function ensureUniqueEmailWithinCompany(string $email, int $companyId, ?int $ignoreUserId = null): void
    {
        $query = User::where('email', $email)
            ->where('company_id', $companyId);

        if ($ignoreUserId) {
            $query->where('id', '!=', $ignoreUserId);
        }

        if ($query->exists()) {
            throw new \Exception('Email already exists for this company.');
        }
    }

    protected function updateAffiliateReporting(User $user, $reportingUserId = null, $authUser = null)
    {
        if (!$authUser) {
            $authUser = $this->userRepo->findUserById(Auth::id());
        }

        $activeReportingUser = $user->reportingUsers()
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            })
            ->first();

        if ($reportingUserId === null) {
            if ($activeReportingUser) {
                $oldReportingUser = User::find($activeReportingUser->reporting_user_id);
                $activeReportingUser->end_date = now()->subDay();
                $activeReportingUser->save();

                $this->updateReportingHierarchy($user);
                if ($oldReportingUser) {
                    $this->updateReportingHierarchy($oldReportingUser);
                }
            }
            return;
        }

        $reportingUser = User::find($reportingUserId);
        if (!$reportingUser) {
            throw new \Exception('Reporting user not found');
        }

        if ($user->id == $reportingUser->id) {
            throw new \Exception('You cannot select yourself as a reporting user');
        }

        if (in_array($reportingUser->id, json_decode($user->junior_user ?? "[]"))) {
            throw new \Exception("You cannot select {$reportingUser->name} as a reporting user, as they are already your junior");
        }

        if ($activeReportingUser && $activeReportingUser->reporting_user_id == $reportingUser->id) {
            return;
        }

        $oldReportingUser = null;
        if ($activeReportingUser) {
            $oldReportingUser = User::find($activeReportingUser->reporting_user_id);
            $activeReportingUser->end_date = now()->subDay();
            $activeReportingUser->save();
        }

        $this->userRepo->createUserReporting([
            'user_id' => $user->id,
            'reporting_user_id' => $reportingUser->id,
            'start_date' => now(),
            'created_by' => $authUser->id,
        ]);

        $this->updateReportingHierarchy($user);

        if ($oldReportingUser) {
            $this->updateReportingHierarchy($oldReportingUser);
        }

        $this->updateReportingHierarchy($reportingUser);
    }

    protected function updateReportingHierarchy(User $user)
    {
        $user->senior_user = ReportingService::getAllSenior($user->id);
        $user->junior_user = ReportingService::getAllJunior($user->id);
        $user->save();
    }
}

