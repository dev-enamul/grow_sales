<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\MeasurmentUnit;
use App\Traits\PaginatorTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MeasurmentUnitController extends Controller
{
    use PaginatorTrait;

    public function index(Request $request)
    {
        try {
            $keyword = $request->keyword;
            $selectOnly = $request->boolean('select');
            
            $query = MeasurmentUnit::where('company_id', Auth::user()->company_id)
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', '%' . $keyword . '%')
                          ->orWhere('abbreviation', 'like', '%' . $keyword . '%');
                    });
                });

            if ($selectOnly) {
                $units = $query->select('id', 'name', 'abbreviation')
                    ->where('is_active', true)
                    ->latest()
                    ->take(10)
                    ->get();
                return success_response($units);
            }

            $sortBy = $request->input('sort_by');
            $sortOrder = $request->input('sort_order', 'asc');

            $allowedSorts = ['name', 'abbreviation', 'is_active', 'created_at'];
            if ($sortBy && in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->latest();
            }

            $measurmentUnits = $this->paginateQuery(
                $query->select('uuid', 'name', 'abbreviation', 'is_active', 'created_at'),
                $request
            );

            return success_response($measurmentUnits);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:measurment_units,name,NULL,id,company_id,' . Auth::user()->company_id,
                'abbreviation' => 'nullable|string|max:10',
            ]);

            $measurmentUnit = MeasurmentUnit::create([
                'name' => $request->input('name'),
                'abbreviation' => $request->input('abbreviation'),
                'company_id' => Auth::user()->company_id,
                'is_active' => true,
            ]);

            return success_response([
                'id' => $measurmentUnit->id,
                'uuid' => $measurmentUnit->uuid,
            ], 'Measurement unit created successfully!', 201);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function show($uuid)
    {
        try {
            $measurmentUnit = MeasurmentUnit::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$measurmentUnit) {
                return error_response('Measurement unit not found', 404);
            }

            return success_response([
                'uuid' => $measurmentUnit->uuid,
                'name' => $measurmentUnit->name,
                'abbreviation' => $measurmentUnit->abbreviation,
                'is_active' => $measurmentUnit->is_active,
                'created_at' => $measurmentUnit->created_at,
            ]);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function update(Request $request, $uuid)
    {
        try {
            $measurmentUnit = MeasurmentUnit::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$measurmentUnit) {
                return error_response('Measurement unit not found', 404);
            }

            $request->validate([
                'name' => 'required|string|max:255|unique:measurment_units,name,' . $measurmentUnit->id . ',id,company_id,' . Auth::user()->company_id,
                'abbreviation' => 'nullable|string|max:10',
                'is_active' => 'nullable|boolean',
            ]);

            $measurmentUnit->update([
                'name' => $request->input('name'),
                'abbreviation' => $request->input('abbreviation'),
                'is_active' => $request->input('is_active', $measurmentUnit->is_active),
            ]);

            return success_response(null, 'Measurement unit updated successfully!');
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }

    public function destroy($uuid)
    {
        try {
            $measurmentUnit = MeasurmentUnit::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$measurmentUnit) {
                return error_response('Measurement unit not found', 404);
            }

            $measurmentUnit->delete();

            return success_response(null, 'Measurement unit deleted successfully.');
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }
}

