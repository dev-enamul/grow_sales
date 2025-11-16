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
            if (!$user || !$user->affiliate) {
                return error_response('Affiliate not found', 404);
            }

            return success_response([
                'uuid' => $user->uuid,
                'affiliate_id' => $user->affiliate->affiliate_id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'profile_image' => $user->profile_image,
                'status' => $user->affiliate->status,
                'referred_by' => $user->affiliate->referred_by,
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
