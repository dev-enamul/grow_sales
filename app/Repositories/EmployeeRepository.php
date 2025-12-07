<?php 
namespace App\Repositories;

use App\Models\DesignationLog;
use App\Models\User;
use App\Traits\PaginatorTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeRepository
{
    use PaginatorTrait; 
    public function getAllEmployees($request)
    {
        $user = Auth::user();
        $selectOnly = $request->boolean('select');
        $keyword = $request->input('keyword');
        $designationId = $request->input('designation_id');
        $status = $request->input('status', null);
        $isResigned = $request->input('is_resigned', null);
        $sortBy = $request->input('sort_by');
        $sortOrder = strtolower($request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = User::with([
                'currentDesignation.designation',
                'reportingUsers' => function ($relation) {
                    $relation->where(function ($q) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>', now());
                    });
                },
            ])
            ->where('company_id', $user->company_id)
            ->where('user_type', 'employee');

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%')
                    ->orWhere('phone', 'like', '%' . $keyword . '%')
                    ->orWhere('user_id', 'like', '%' . $keyword . '%');
            });
        }

        if ($designationId) {
            $query->whereHas('currentDesignation', function ($designationQuery) use ($designationId) {
                $designationQuery->where('designation_id', $designationId);
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        if ($isResigned !== null && $isResigned !== '') {
            $query->where('is_resigned', (int) $isResigned);
        }

        $allowedSorts = [
            'name' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'created_at' => 'created_at',
        ];

        if ($sortBy && isset($allowedSorts[$sortBy])) {
            $query->orderBy($allowedSorts[$sortBy], $sortOrder);
        } else {
            $query->latest();
        }

        if ($selectOnly) {
            return $query
                ->select('id', 'name')
                ->latest()
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                    ];
                });
        }

        $paginated = $this->paginateQuery($query, $request);

        $mapped = collect($paginated['data'])->map(function ($user) {
            $seniorUserName = null;
            $seniorUserIds = json_decode($user->senior_user ?? '[]', true);
            if (is_array($seniorUserIds) && !empty($seniorUserIds)) {
                $firstSeniorUser = User::find($seniorUserIds[0]);
                $seniorUserName = $firstSeniorUser ? $firstSeniorUser->name : null;
            }

            $currentDesignation = optional($user->currentDesignation);
            $designationTitle = optional($currentDesignation->designation)->title;
            $designationId = $currentDesignation->designation_id ?? null;

            $activeReporting = $user->reportingUsers->first();

            return [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'employee_id' => $user->user_id,
                'profile_image' => $user->profile_image,
                'profile_image_url' => getFileUrl($user->profile_image),
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'senior_user' => $seniorUserName,
                'designation' => $designationTitle,
                'designation_id' => $designationId,
                'status' => $user->status,
                'is_resigned' => $user->is_resigned,
                'reporting_id' => $activeReporting ? $activeReporting->reporting_user_id : null,
                'created_at' => formatDate($user->created_at),
            ];
        });

        $paginated['data'] = $mapped->values();

        return $paginated;
    }

 



    public function createDesignationLog($data)
    {
        return DesignationLog::create($data);
    }  
 
}
