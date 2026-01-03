<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Affiliate\AffiliateStoreRequest;
use App\Http\Requests\Affiliate\AffiliateUpdateRequest;
use App\Services\AffiliateService;
use Exception;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    protected $affiliateService;

    public function __construct(AffiliateService $affiliateService)
    {
        $this->affiliateService = $affiliateService;
    }

    public function index(Request $request)
    {
        try {
            return success_response($this->affiliateService->getAllAffiliates($request));
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function store(AffiliateStoreRequest $request)
    {
        try {
            $result = $this->affiliateService->createAffiliate($request);
            return success_response(null, $result['message']);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function show($uuid)
    {
        try {
            $user = $this->affiliateService->show($uuid);
            if (!$user || !$user->isAffiliate()) {
                return error_response('Affiliate not found', 404);
            }

            // Load reportingUsers relationship
            $user->load('reportingUsers');

            // Get current reporting user from the collection
            $currentReportingUser = $user->reportingUsers
                ->filter(function ($reporting) {
                    return $reporting->end_date === null || $reporting->end_date > now();
                })
                ->first();
            $reportingUserId = $currentReportingUser ? $currentReportingUser->reporting_user_id : null;

            return success_response([
                'uuid' => $user->uuid,
                'affiliate_id' => $user->user_id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'profile_image' => $user->profile_image,
                'profile_image_url' => getFileUrl($user->profile_image),
                'status' => $user->status,
                'referred_by' => $user->referred_by,
                'referral_code' => $user->user_id,
                'created_at' => $user->created_at,
                'reporting_id' => $reportingUserId,
            ]);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function update(AffiliateUpdateRequest $request, $uuid)
    {
        try {
            $result = $this->affiliateService->updateAffiliate($uuid, $request);
            return success_response(null, $result['message']);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function destroy($uuid)
    {
        try {
            $result = $this->affiliateService->deleteAffiliate($uuid);
            return success_response(null, $result['message']);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }
}
