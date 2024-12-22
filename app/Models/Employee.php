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
        'uuid',
        'user_id',
        'employee_id',
        'signature',
        'ref_id',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public static function generateNextEmployeeId(){ 
        $largest_employee_id = Employee::where('employee_id', 'like', 'EMP-%') 
        ->pluck('employee_id')
                ->map(function ($id) {
                        return preg_replace("/[^0-9]/", "", $id);
                }) 
        ->max(); 
        $largest_employee_id++;
        $new_employee_id = 'EMP-' . str_pad($largest_employee_id, 6, '0', STR_PAD_LEFT);
        return $new_employee_id;
    } 

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function currentDesignation()
    {
        return $this->hasOne(EmployeeDesignation::class, 'employee_id', 'id')
                    ->whereNull('end_date');
    } 

    public function designationOnDate($date = null)
    {
        $date = $date ? Carbon::parse($date) : now(); 
        return $this->hasOne(EmployeeDesignation::class, 'employee_id', 'id')
                    ->where('start_date', '<=', $date)
                    ->where(function ($query) use ($date) {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>=', $date);
                    });
    }


    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
    
}
