<?php

namespace App\Models;

use App\Traits\ActionTrackable; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Lead extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable;

    protected $fillable = [
        'uuid',
        'company_id',
        'lead_id',
        'user_id',
        'customer_id',
        'lead_categorie_id',
        'priority',
        'price',
        'next_followup_date',
        'last_contacted_at',
        'assigned_to',
        'lead_source_id',
        'status',
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
 
    public function leadCategory()
    {
        return $this->belongsTo(LeadCategory::class, 'lead_categorie_id');
    }

    public function leadSource()
    {
        return $this->belongsTo(LeadSource::class);
    }
 
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
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
    
    public function products()
    {
        return $this->hasManyThrough(Product::class, LeadProduct::class, 'lead_id', 'id', 'id', 'product_id');
    }

    public static function generateNextLeadId(){
        $largest_lead_id = Lead::where('lead_id', 'like', 'LEAD-%') 
        ->pluck('lead_id')
                ->map(function ($id) {
                        return preg_replace("/[^0-9]/", "", $id);
                }) 
        ->max(); 
        $largest_lead_id++;
        $new_lead_id = 'LEAD-' . str_pad($largest_lead_id, 6, '0', STR_PAD_LEFT);
        return $new_lead_id;
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
