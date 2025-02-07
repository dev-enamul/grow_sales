<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'customer_id',
        'lead_id',
        'product_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $dates = ['deleted_at'];

    // Relationship with Company
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Relationship with User (user who created the lead product)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Relationship with Lead
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
 
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
 
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
 
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Relationship with User (who deleted the lead product)
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

}
