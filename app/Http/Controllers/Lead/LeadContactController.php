<?php

namespace App\Http\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadContact;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeadContactController extends Controller
{
    /**
     * Add a contact to a lead
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $authUser = Auth::user();
            $companyId = $authUser->company_id;

            $request->validate([
                'lead_uuid' => 'required|uuid|exists:leads,uuid',
                'contact_id' => 'required|integer|exists:contacts,id',
                'relationship_or_role' => 'nullable|string|max:255',
                'is_decision_maker' => 'nullable|boolean',
            ]);

            $lead = Lead::where('uuid', $request->lead_uuid)
                ->where('company_id', $companyId)
                ->first();

            if (!$lead) {
                return error_response('Lead not found', 404);
            }

            // Check if contact is already associated with this lead
            $existingLeadContact = LeadContact::where('lead_id', $lead->id)
                ->where('contact_id', $request->contact_id)
                ->where('company_id', $companyId)
                ->first();

            if ($existingLeadContact) {
                return error_response('Contact is already associated with this lead', 400);
            }

            // Create lead contact relationship
            $leadContact = LeadContact::create([
                'company_id' => $companyId,
                'lead_id' => $lead->id,
                'contact_id' => $request->contact_id,
                'relationship_or_role' => $request->relationship_or_role ?? 'Contact',
                'is_decision_maker' => $request->is_decision_maker ?? false,
                'created_by' => $authUser->id,
            ]);

            DB::commit();
            return success_response([
                'id' => $leadContact->id,
                'uuid' => $leadContact->uuid,
            ], 'Contact added to lead successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return error_response($e->getMessage(), 500);
        }
    }
}

