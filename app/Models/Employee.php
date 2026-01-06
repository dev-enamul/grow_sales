<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Employee extends Model
{
    use HasFactory; 
    protected $fillable = [
        'user_id',
        'employee_id',
        'signature',
        'is_admin',
        'contact_id',
        'shift_id',
        'joining_date',
        'salary',
        'weekend_days',
        'referred_by',
        'status',
        'is_resigned',
        'resigned_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'weekend_days' => 'array',
    ];

    public static function generateNextEmployeeId(){
        // Check users table for existing IDs, including soft deleted ones
        $largestFromUsers = \App\Models\User::withTrashed()
            ->where('user_id', 'like', 'EMP-%')
            ->pluck('user_id')
            ->map(function ($id) {
                return (int) preg_replace("/[^0-9]/", "", $id);
            })
            ->max() ?? 0;

        $largest_employee_id = $largestFromUsers;
        $largest_employee_id++;
        
        $new_employee_id = 'EMP-' . str_pad($largest_employee_id, 6, '0', STR_PAD_LEFT);
        return $new_employee_id;
    } 

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function shift()
    {
        return $this->belongsTo(WorkShift::class, 'shift_id');
    }

    public function currentDesignation()
    {
        return $this->hasOne(DesignationLog::class, 'employee_id', 'id')
                    ->whereNull('end_date') 
                    ->orWhere('end_date', '>=', now());
    }
    

    public function designationOnDate($date = null)
    {
        $date = $date ? Carbon::parse($date) : now(); 
        return $this->hasOne(DesignationLog::class, 'employee_id', 'id')
                    ->where('start_date', '<=', $date)
                    ->where(function ($query) use ($date) {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>=', $date);
                    });
    }
 
    
}
