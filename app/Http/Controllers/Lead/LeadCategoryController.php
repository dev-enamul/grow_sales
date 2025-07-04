<?php

namespace App\Http\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Models\LeadCategory;
use App\Traits\PaginatorTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadCategoryController extends Controller
{
    use PaginatorTrait;

    public function index(Request $request)
    {
        try {
            $keyword = $request->keyword;
            $selectOnly = $request->boolean('select');
            $query = LeadCategory::where('company_id', Auth::user()->company_id)
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('title', 'like', '%' . $keyword . '%')
                        ->orWhere('serial', 'like', '%' . $keyword . '%');
                })
                ->when($request->status, function ($query) use ($request) {
                    $query->where('status', $request->status);
                });

            if ($selectOnly) {
                $categories = $query->select('id', 'title')->latest()->take(10)->get();
                return success_response($categories);
            }

            $sortBy = $request->input('sort_by');
            $sortOrder = $request->input('sort_order', 'asc');

            $allowedSorts = ['title', 'serial'];
            if ($sortBy && in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->latest();
            }

            $leadCategories = $this->paginateQuery($query->select('id', 'uuid', 'title', 'serial', 'status'), $request);

            return success_response($leadCategories);
        } catch (\Exception $e) {
            return error_response($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([ 
            'title' => 'required|string|max:255', 
            'status' => 'required|in:0,1',
            'serial' => 'required|integer',
        ]);

        try {
            $authUser = Auth::user();

            $leadCategory = LeadCategory::create([
                'company_id' => $authUser->company_id,
                'title' => $request->title,
                'slug' => getSlug(new LeadCategory(), $request->title),
                'status' => $request->status,
                'serial' => $request->serial,
                'created_by' => $authUser->id,
            ]);

            return success_response(null, "Lead category created successfully");
        } catch (Exception $e) {
            return error_response($e->getMessage());
        }
    }
 
    
 
    public function show($uuid)
    {
        try {
            $leadCategory = LeadCategory::where('uuid', $uuid)->select('id','uuid','title','status','serial')->first(); 
            if (!$leadCategory) {
                return error_response(null, 404, "Lead category not found");
            } 
            return success_response($leadCategory);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500, "An error occurred while fetching the lead category");
        }
    }  
    
    public function update(Request $request, $uuid)
    { 
        $request->validate([
            'title' => 'required|string|max:255', 
            'status' => 'required|in:0,1',
            'serial' => 'required|integer',
        ]);

        try {
            $leadCategory = LeadCategory::where('uuid', $uuid)->first();

            if (!$leadCategory) {
                return error_response(null, 404, "Lead category not found");
            }

            $leadCategory->update([
                'title' => $request->title,
                'slug' => getSlug($leadCategory, $request->title),
                'status' => $request->status,
                'serial' => $request->serial,
                'updated_by' => Auth::user()->id,
            ]); 
            return success_response(null, "Lead category updated successfully");
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500, "An error occurred while updating the lead category");
        }
    }
 
    public function destroy($uuid)
    {
        try {
            $leadCategory = LeadCategory::where('uuid', $uuid)->first();

            if (!$leadCategory) {
                return error_response(null, 404, "Lead category not found");
            } 
            $leadCategory->delete(); 
            return success_response(null, "Lead category deleted successfully");
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500, "An error occurred while deleting the lead category");
        }
    }
}
