<?php

namespace App\Http\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Models\LeadCategory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadCategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $status = $request->status;
            if($status){
                $leadCategories = LeadCategory::where('status',$status);
            }else{
                $leadCategories = new LeadCategory(); 
            }

            $leadCategories = $leadCategories->select('id','uuid','title','status','serial')->get();
            
            return success_response($leadCategories);
        } catch (Exception $e) {
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
