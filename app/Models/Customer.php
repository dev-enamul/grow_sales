<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use App\Traits\FindByUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory, ActionTrackable, SoftDeletes, FindByUuidTrait; 

    protected $fillable = [
        'uuid',
        'company_id',
        'customer_code',
        'organization_id',
        'primary_contact_id',
        'lead_id',
        'referred_by',
        'total_sales',
        'total_sales_amount',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'total_sales' => 'integer',
        'total_sales_amount' => 'decimal:2',
    ];
    
    public static function generateNextCustomerId(){ 
        $largest_customer_id = Customer::where('customer_code', 'like', 'CUS-%') 
        ->pluck('customer_code')
                ->map(function ($id) {
                        return (int) substr($id, 4);
                }) 
        ->max(); 
        $largest_customer_id++;
        $new_customer_id = 'CUS-' . str_pad($largest_customer_id, 6, '0', STR_PAD_LEFT);
        return $new_customer_id;
    } 

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function primaryContact()
    {
        return $this->belongsTo(Contact::class, 'primary_contact_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by');
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
