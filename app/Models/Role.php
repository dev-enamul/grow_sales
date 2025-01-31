<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Role extends Model
{
    use HasFactory, SoftDeletes;  
    
    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'guard_name',
        'created_by',
        'updated_by',
        'deleted_by'
    ];
 
    protected static function booted()
    {
        static::creating(function ($role) {
            if (!$role->uuid) {
                $role->uuid = (string) Str::uuid();
            }
        });
    }  

    public function company()
    {
        return $this->belongsTo(Company::class);
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
}
