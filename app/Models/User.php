<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;  

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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
    ]; 

    public function userContact()
    {
        return $this->hasMany(UserContact::class);
    }

    public function userAddress()
    {
        return $this->hasMany(UserAddress::class);
    }
 

    public function reportingUsers()
    {
        return $this->hasMany(UserReporting::class, 'user_id');
    }

    
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }  

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id', 'id');
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
