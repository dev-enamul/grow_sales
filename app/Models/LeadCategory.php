<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LeadCategory extends Model
{
    use HasFactory, SoftDeletes;
 
    protected $fillable = [
        'uuid',           
        'company_id',   
        'title',        
        'slug',         
        'status',       
        'serial',       
        'created_by',   
        'updated_by',   
        'deleted_by',   
    ];
 
    protected $dates = ['deleted_at'];
 
    protected static function booted()
    {
        static::creating(function ($leadCategory) { 
            if (!$leadCategory->uuid) {
                $leadCategory->uuid = (string) Str::uuid();
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
