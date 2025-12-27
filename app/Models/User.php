<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, ActionTrackable, HasFactory, Notifiable, SoftDeletes;
    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'phone',
        'email',
        'password',
        'user_type',
        'profile_image',
        'marital_status',
        'dob',
        'blood_group',
        'gender',
        // Employee and Affiliate fields
        'user_id',
        'signature',
        'is_admin',
        'salary',
        'is_resigned',
        'resigned_at',
        'referred_by',
        'status',
        'senior_user',
        'junior_user',
        'role_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    
    

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'salary' => 'decimal:2',
        'is_admin' => 'boolean',
        'is_resigned' => 'boolean',
        'resigned_at' => 'date',
        'status' => 'integer',
    ]; 
    public function reportingUsers()
    {
        return $this->hasMany(UserReporting::class, 'user_id');
    }

    
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function designationLogs()
    {
        return $this->hasMany(DesignationLog::class, 'user_id', 'id');
    }

    public function currentDesignation()
    {
        return $this->hasOne(DesignationLog::class, 'user_id', 'id')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    // Helper methods to check user type
    public function isEmployee()
    {
        return $this->user_type === 'employee';
    }

    public function isAffiliate()
    {
        return $this->user_type === 'affiliate';
    }

    public function isCustomer()
    {
        return $this->user_type === 'customer';
    }

    // Generate next employee ID
    public static function generateNextEmployeeId()
    {
        $largest_employee_id = User::where('user_id', 'like', 'EMP-%')
            ->where('user_type', 'employee')
            ->pluck('user_id')
            ->map(function ($id) {
                return preg_replace("/[^0-9]/", "", $id);
            })
            ->max();
        $largest_employee_id = $largest_employee_id ? $largest_employee_id : 0;
        $largest_employee_id++;
        return 'EMP-' . str_pad($largest_employee_id, 6, '0', STR_PAD_LEFT);
    }

    // Generate next affiliate ID
    public static function generateNextAffiliateId()
    {
        $largest_affiliate_id = User::where('user_id', 'like', 'AFF-%')
            ->where('user_type', 'affiliate')
            ->pluck('user_id')
            ->map(function ($id) {
                return preg_replace("/[^0-9]/", "", $id);
            })
            ->max();
        $largest_affiliate_id = $largest_affiliate_id ? $largest_affiliate_id : 0;
        $largest_affiliate_id++;
        return 'AFF-' . str_pad($largest_affiliate_id, 6, '0', STR_PAD_LEFT);
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'user_id', 'id');
    }
 
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id','id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
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
