<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\LeadSource;
use App\Traits\PaginatorTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadSourceController extends Controller
{
    use PaginatorTrait;

    public function index(Request $request)
    {
        try {
            $keyword = $request->keyword;
            $selectOnly = $request->boolean('select');
            $query = LeadSource::where('company_id', Auth::user()->company_id)
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('slug', 'like', '%' . $keyword . '%');
                });

            if ($selectOnly) {
                $leadSources = $query->select('id', 'name')->latest()->take(10)->get();
                return success_response($leadSources);
            }

            $sortBy = $request->input('sort_by');
            $sortOrder = $request->input('sort_order', 'asc');

            $allowedSorts = ['name', 'slug'];
            if ($sortBy && in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->latest();
            }

            $leadSources = $this->paginateQuery($query->select('uuid', 'name', 'slug', 'status'), $request);

            return success_response($leadSources);
        } catch (\Exception $e) {
            return error_response($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255', 
        ]);  

        try {
            $leadSource = new LeadSource();
            $leadSource->name = $request->input('name');
            $leadSource->slug = getSlug(new LeadSource(), $request->input('name')); 
            $leadSource->status = $request->status;
            $leadSource->save();

            return success_response(null, 'Lead source created successfully!', 201);
        } catch (\Exception $e) {
            return error_response($e->getMessage());
        }
    }

    public function update(Request $request, $uuid)
    {
        $request->validate([
            'name' => 'required|string|max:255', 
        ]);

        try {
            $leadSource = LeadSource::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$leadSource) {
                return error_response('Lead source not found', 404);
            }

            $leadSource->name = $request->input('name');
            $leadSource->slug = getSlug(new LeadSource(),$request->input('name')); 
            $leadSource->status = $request->status??$leadSource->status; 
            $leadSource->save();

            return success_response(null, 'Lead source updated successfully!');
        } catch (\Exception $e) {
            return error_response($e->getMessage());
        }
    }

    public function destroy($uuid)
    {
        try {
            $leadSource = LeadSource::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$leadSource) {
                return error_response('Lead source not found', 404);
            } 
            $leadSource->delete(); 
            return success_response(null, 'Lead source deleted successfully.');
        } catch (\Exception $e) {
            return error_response($e->getMessage());
        }
    }
}
