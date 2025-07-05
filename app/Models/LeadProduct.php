<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ProductUnit;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use App\Traits\ActionTrackable;

class LeadProduct extends Model
{
    use HasFactory, ActionTrackable, SoftDeletes;

    protected $fillable = [
        'company_id',
        'lead_id',
        'product_id',
        'customer_id',
        'user_id',
        'product_unit_id',
        "area_id",
        'product_category_id',
        'product_sub_category_id',
        'qty',
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

    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class);
    }

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function productSubCategory()
    {
        return $this->belongsTo(ProductSubCategory::class);
    }
 
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
 
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Relationship with User (who deleted the lead product)
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

}
