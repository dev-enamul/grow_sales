<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use App\Models\DesignationLog;
use App\Models\User;
use App\Traits\PaginatorTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DesignationController extends Controller
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

            $query = Designation::where('company_id', Auth::user()->company_id);

            // Keyword search
            if ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('title', 'like', "%{$keyword}%")
                        ->orWhere('slug', 'like', "%{$keyword}%");
                });
            }

            // Select only mode for dropdowns
            if ($selectOnly) {
                $list = $query
                    ->select('id', 'title')
                    ->latest()
                    ->limit(10)
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->title,
                        ];
                    });
                return success_response($list);
            }

            // Build active employee count sub-query (only for list mode)
            $activeEmployeeCounts = DesignationLog::select(
                'designation_id',
                DB::raw('count(distinct user_id) as employee_count')
            )
                ->where(function ($q) {
                    $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', now());
                })
                ->whereHas('user', function ($q) {
                    $q->where('user_type', 'employee')
                        ->where(function ($q2) {
                            $q2->where('is_resigned', 0)
                                ->orWhereNull('is_resigned');
                        });
                })
                ->groupBy('designation_id');

            $query->leftJoinSub($activeEmployeeCounts, 'active_employee_counts', function ($join) {
                $join->on('designations.id', '=', 'active_employee_counts.designation_id');
            });

            $query->select(
                'designations.*',
                DB::raw('COALESCE(active_employee_counts.employee_count, 0) as total_employees')
            );

            // Sorting
            $sortBy = $request->input('sort_by');
            $sortOrder = strtolower($request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';
            $allowedSorts = ['title', 'created_at', 'total_employees'];

            if ($sortBy && in_array($sortBy, $allowedSorts)) {
                if ($sortBy === 'total_employees') {
                    $query->orderBy('total_employees', $sortOrder);
                } else {
                    $query->orderBy("designations.{$sortBy}", $sortOrder);
                }
            } else {
                $query->latest('designations.created_at');
            }

            // Pagination
            $paginated = $this->paginateQuery($query, $request);

            // Map data
            $paginated['data'] = collect($paginated['data'])->map(function ($item) {
                return [
                    'id' => $item->uuid, 
                    'title' => $item->title,
                    'slug' => $item->slug,
                    'total_employees' => (int) ($item->total_employees ?? 0),
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
                'title' => 'required|string|max:255',
            ]);

            $designation = Designation::create([
                'company_id' => Auth::user()->company_id,
                'title' => $request->title,
                'slug' => getSlug(new Designation(), $request->title),
            ]);

            DB::commit();
            return success_response([
                'id' => $designation->id,
                'uuid' => $designation->uuid,
                'title' => $designation->title,
                'slug' => $designation->slug,
            ], 'Designation created successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return error_response($e->errors(), 422, 'Validation failed');
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
            $designation = Designation::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->with(['createdBy:id,name', 'updatedBy:id,name', 'deletedBy:id,name'])
                ->first();

            if (!$designation) {
                return error_response('Designation not found', 404);
            }

            return success_response([
                'id' => $designation->id,
                'uuid' => $designation->uuid,
                'title' => $designation->title,
                'slug' => $designation->slug,
                'created_by' => $designation->createdBy ? $designation->createdBy->name : null,
                'updated_by' => $designation->updatedBy ? $designation->updatedBy->name : null,
                'created_at' => formatDate($designation->created_at),
                'updated_at' => formatDate($designation->updated_at),
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
                'title' => 'required|string|max:255',
            ]);

            $designation = Designation::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$designation) {
                return error_response('Designation not found', 404);
            }

            // If title changed, regenerate slug
            if ($designation->title !== $request->title) {
                // Check if slug already exists for other records
                $baseSlug = \Illuminate\Support\Str::slug($request->title);
                $slug = $baseSlug;
                $count = 1;
                
                while (Designation::where('company_id', Auth::user()->company_id)
                    ->where('slug', $slug)
                    ->where('id', '!=', $designation->id)
                    ->exists()) {
                    $slug = $baseSlug . '-' . $count;
                    $count++;
                }
                
                $designation->slug = $slug;
            }
            
            $designation->title = $request->title;
            $designation->save();

            DB::commit();
            return success_response([
                'id' => $designation->id,
                'uuid' => $designation->uuid,
                'title' => $designation->title,
                'slug' => $designation->slug,
            ], 'Designation updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return error_response($e->errors(), 422, 'Validation failed');
        } catch (Exception $e) {
            DB::rollBack();
            return error_response($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($uuid)
    {
        DB::beginTransaction();
        try {
            $designation = Designation::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$designation) {
                return error_response('Designation not found', 404);
            }

            // Check if there are active employees with this designation
            $activeEmployeesCount = DesignationLog::where('designation_id', $designation->id)
                ->where(function ($q) {
                    $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', now());
                })
                ->whereHas('user', function ($q) {
                    $q->where('user_type', 'employee')
                        ->where(function ($q2) {
                            $q2->where('is_resigned', 0)
                                ->orWhereNull('is_resigned');
                        });
                })
                ->count();

            if ($activeEmployeesCount > 0) {
                DB::rollBack();
                return error_response(
                    "Cannot delete designation. There are {$activeEmployeesCount} active employee(s) assigned to this designation.",
                    422,
                    'Cannot delete designation with active employees'
                );
            }

            $designation->delete();

            DB::commit();
            return success_response(null, 'Designation deleted successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return error_response($e->getMessage(), 500);
        }
    }
}
