<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Designation extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable; 
    protected $table = 'designations'; 
    protected $fillable = [
        'company_id',
        'title',
        'slug',
        'department',
        'level',
        'salary_range_min',
        'salary_range_max',
        'created_by',
        'updated_by',
        'deleted_by'
    ];
 
    protected static function booted()
    {
        static::creating(function ($designation) {
            if (!$designation->uuid) {
                $designation->uuid = (string) Str::uuid();
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
