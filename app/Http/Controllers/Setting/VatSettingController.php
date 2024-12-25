<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\VatSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VatSettingController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->status;
        $keyword = $request->keyword;

        $vatSettings = VatSetting::where('company_id', Auth::user()->company_id)
            ->when($status, function ($query) use ($status) {
                $query->where('is_active', $status);
            })
            ->when($keyword, function ($query) use ($keyword) {
                $query->where('name', 'like', '%' . $keyword . '%');
            })
            ->when($request->is_select2, function ($query) {
                $query->select('uuid', 'name', 'vat_percentage');
            }, function ($query) { 
                $query->select('uuid', 'name', 'vat_percentage', 'is_active', 'note');
            })
            ->paginate(10);

        return success_response($vatSettings);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'vat_percentage' => 'required|numeric|between:0,100',
        ]);

        $vatSetting = new VatSetting();
        $vatSetting->name = $request->input('name');
        $vatSetting->vat_percentage = $request->input('vat_percentage');
        $vatSetting->company_id = Auth::user()->company_id;
        $vatSetting->created_by = Auth::id();
        $vatSetting->save();

        return success_response($vatSetting, 'VAT setting created successfully!', 201);
    }
 
    public function show($uuid)
    {
        $vatSetting = VatSetting::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->select('uuid', 'name', 'vat_percentage', 'is_active', 'note')
            ->first();

        if (!$vatSetting) {
            return error_response("VAT setting not found", 404);
        } 
        return success_response($vatSetting);
    }
 
    public function update(Request $request, $uuid)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'vat_percentage' => 'required|numeric|between:0,100',
        ]); 

        $vatSetting = VatSetting::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->first(); 
        if (!$vatSetting) {
            return error_response("VAT setting not found", 404);
        } 
        $vatSetting->name = $request->input('name');
        $vatSetting->vat_percentage = $request->input('vat_percentage');
        $vatSetting->is_active = $request->input('is_active', $vatSetting->is_active);
        $vatSetting->updated_by = Auth::id();
        $vatSetting->save();
        return success_response($vatSetting, 'VAT setting updated successfully!');
    }
}
