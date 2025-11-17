<?php

namespace App\Http\Controllers\Campaign;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Traits\PaginatorTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignController extends Controller
{
    use PaginatorTrait;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $selectOnly = $request->boolean('select');
            $keyword = $request->keyword;

            $query = Campaign::where('company_id', Auth::user()->company_id);

            // Keyword search
            if ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('slug', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%");
                });
            }

            // Select only mode for dropdowns
            if ($selectOnly) {
                $list = $query
                    ->select('id', 'name')
                    ->latest()
                    ->limit(10)
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                        ];
                    });
                return success_response($list);
            }

            // Sorting
            $sortBy = $request->input('sort_by');
            $sortOrder = $request->input('sort_order', 'asc');
            $allowedSorts = ['name', 'created_at'];

            if ($sortBy && in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->latest();
            }

            // Pagination
            $paginated = $this->paginateQuery($query, $request);

            // Get frontend URL from env
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            // Remove trailing slash if exists
            $frontendUrl = rtrim($frontendUrl, '/');

            // Map data with URL
            $paginated['data'] = collect($paginated['data'])->map(function ($item) use ($frontendUrl) {
                return [
                    'id' => $item->uuid, 
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'description' => $item->description,
                    'budget' => $item->budget,
                    'campaign_type' => $item->campaign_type,
                    'channel' => $item->channel,
                    'clicks' => $item->clicks,
                    'impressions' => $item->impressions,
                    'target_leads' => $item->target_leads,
                    'target_sales' => $item->target_sales,
                    'target_revenue' => $item->target_revenue,
                    'url' => "{$frontendUrl}/campaign/{$item->slug}",
                    'created_at' => formatDate($item->created_at),
                    'updated_at' => formatDate($item->updated_at),
                ];
            });

            return success_response($paginated);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'budget' => 'nullable|numeric|min:0',
                'campaign_type' => 'nullable|string|max:255',
                'channel' => 'nullable|string|max:255',
                'area_id' => 'nullable|exists:areas,id',
                'target_leads' => 'nullable|integer|min:0',
                'target_sales' => 'nullable|integer|min:0',
                'target_revenue' => 'nullable|numeric|min:0',
            ]);

            $campaign = Campaign::create([
                'company_id' => Auth::user()->company_id,
                'name' => $request->name,
                'slug' => getSlug(new Campaign(), $request->name),
                'description' => $request->description,
                'budget' => $request->budget,
                'campaign_type' => $request->campaign_type,
                'channel' => $request->channel,
                'area_id' => $request->area_id,
                'target_leads' => $request->target_leads,
                'target_sales' => $request->target_sales,
                'target_revenue' => $request->target_revenue,
                'created_by' => Auth::user()->id,
            ]);

            DB::commit();
            return success_response([
                'id' => $campaign->uuid,
                'uuid' => $campaign->uuid,
                'name' => $campaign->name,
                'slug' => $campaign->slug,
                'description' => $campaign->description,
                'budget' => $campaign->budget,
                'campaign_type' => $campaign->campaign_type,
                'channel' => $campaign->channel,
                'area_id' => $campaign->area_id,
                'target_leads' => $campaign->target_leads,
                'target_sales' => $campaign->target_sales,
                'target_revenue' => $campaign->target_revenue,
            ], 'Campaign created successfully', 201);
        } catch (Exception $e) {
            DB::rollBack();
            return error_response($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($uuid)
    {
        try {
            $campaign = Campaign::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->with(['area:id,name', 'createdBy:id,name', 'updatedBy:id,name', 'deletedBy:id,name'])
                ->first();

            if (!$campaign) {
                return error_response('Campaign not found', 404);
            }

            // Get frontend URL from env
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $frontendUrl = rtrim($frontendUrl, '/');

            return success_response([
                'id' => $campaign->uuid,
                'uuid' => $campaign->uuid,
                'name' => $campaign->name,
                'slug' => $campaign->slug,
                'description' => $campaign->description,
                'budget' => $campaign->budget,
                'campaign_type' => $campaign->campaign_type,
                'channel' => $campaign->channel,
                'area' => $campaign->area ? $campaign->area->name : null,
                'area_id' => $campaign->area_id,
                'clicks' => $campaign->clicks,
                'impressions' => $campaign->impressions,
                'target_leads' => $campaign->target_leads,
                'target_sales' => $campaign->target_sales,
                'target_revenue' => $campaign->target_revenue,
                'url' => "{$frontendUrl}/campaign/{$campaign->slug}",
                'created_by' => $campaign->createdBy ? $campaign->createdBy->name : null,
                'updated_by' => $campaign->updatedBy ? $campaign->updatedBy->name : null,
                'deleted_by' => $campaign->deletedBy ? $campaign->deletedBy->name : null,
                'created_at' => formatDate($campaign->created_at),
                'updated_at' => formatDate($campaign->updated_at),
            ]);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $uuid)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'budget' => 'nullable|numeric|min:0',
                'campaign_type' => 'nullable|string|max:255',
                'channel' => 'nullable|string|max:255',
                'area_id' => 'nullable|exists:areas,id',
                'target_leads' => 'nullable|integer|min:0',
                'target_sales' => 'nullable|integer|min:0',
                'target_revenue' => 'nullable|numeric|min:0',
            ]);

            $campaign = Campaign::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$campaign) {
                return error_response('Campaign not found', 404);
            }

            // Update slug if name changed
            if ($campaign->name !== $request->name) {
                $campaign->slug = getSlug(new Campaign(), $request->name);
            }

            $campaign->fill($request->only([
                'name', 'description', 'budget', 'campaign_type', 'channel', 'area_id',
                'target_leads', 'target_sales', 'target_revenue'
            ]));
            $campaign->updated_by = Auth::user()->id;
            $campaign->save();

            DB::commit();
            return success_response([
                'id' => $campaign->uuid,
                'uuid' => $campaign->uuid,
                'name' => $campaign->name,
                'slug' => $campaign->slug,
                'description' => $campaign->description,
                'budget' => $campaign->budget,
                'campaign_type' => $campaign->campaign_type,
                'channel' => $campaign->channel,
                'area_id' => $campaign->area_id,
                'target_leads' => $campaign->target_leads,
                'target_sales' => $campaign->target_sales,
                'target_revenue' => $campaign->target_revenue,
            ], 'Campaign updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return error_response($e->getMessage(), 500);
        }
    }

    /**
     * Track impression (Public - when someone visits campaign URL)
     * This will be called automatically when someone visits the campaign page
     */
    public function trackImpression(Request $request, $slug)
    {
        try {
            $campaign = Campaign::where('slug', $slug)->first();

            if (!$campaign) {
                return error_response('Campaign not found', 404);
            }

            // Increment impressions
            $campaign->increment('impressions');

            // Optional: Log IP address to prevent duplicate counting (you can implement this later)
            // $ip = $request->ip();
            // You can create a campaign_impressions table to track unique impressions per IP

            return success_response([
                'impressions' => $campaign->fresh()->impressions
            ], 'Impression tracked successfully');
        } catch (\Exception $e) {
            Log::error('Campaign impression tracking failed: ' . $e->getMessage());
            return error_response('Failed to track impression', 500);
        }
    }

    /**
     * Track click (Public - when someone clicks on campaign link)
     */
    public function trackClick(Request $request, $slug)
    {
        try {
            $campaign = Campaign::where('slug', $slug)->first();

            if (!$campaign) {
                return error_response('Campaign not found', 404);
            }

            // Increment clicks
            $campaign->increment('clicks');

            return success_response([
                'clicks' => $campaign->fresh()->clicks
            ], 'Click tracked successfully');
        } catch (\Exception $e) {
            Log::error('Campaign click tracking failed: ' . $e->getMessage());
            return error_response('Failed to track click', 500);
        }
    }

    /**
     * Update campaign metrics manually (Authenticated - for admin)
     */
    public function updateMetrics(Request $request, $uuid)
    {
        try {
            $request->validate([
                'impressions' => 'nullable|integer|min:0',
                'clicks' => 'nullable|integer|min:0',
                'spent_amount' => 'nullable|numeric|min:0',
            ]);

            $campaign = Campaign::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$campaign) {
                return error_response('Campaign not found', 404);
            }

            if ($request->has('impressions')) {
                $campaign->impressions = $request->impressions;
            }

            if ($request->has('clicks')) {
                $campaign->clicks = $request->clicks;
            }

            if ($request->has('spent_amount')) {
                $campaign->spent_amount = $request->spent_amount;
            }

            $campaign->save();

            return success_response([
                'impressions' => $campaign->impressions,
                'clicks' => $campaign->clicks,
                'spent_amount' => $campaign->spent_amount,
            ], 'Metrics updated successfully');
        } catch (\Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }
 
}
