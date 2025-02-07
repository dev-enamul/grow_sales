<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Followup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'user_id',
        'customer_id',
        'lead_id',
        'lead_categorie_id',
        'purchase_probability',
        'price',
        'next_followup_date',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $dates = ['deleted_at'];
 
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
 
    public function user()
    {
        return $this->belongsTo(User::class);
    }
 
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
 
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }  

    public function leadCategory()
    {
        return $this->belongsTo(LeadCategory::class, 'lead_categorie_id');
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
