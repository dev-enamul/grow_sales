<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use App\Traits\FindByUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LeadProduct extends Model
{
    use HasFactory, ActionTrackable, SoftDeletes, FindByUuidTrait;

    protected $fillable = [
        'uuid',
        'company_id',
        'lead_id',
        'type',
        'property_unit_id',
        'area_id',
        'product_category_id',
        'product_sub_category_id',
        'product_id',
        'quantity',
        'other_price',
        'discount',
        'negotiated_price',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // Accessor for qty (backward compatibility)
    public function getQtyAttribute()
    {
        return $this->quantity;
    }

    // Mutator for qty (backward compatibility)
    public function setQtyAttribute($value)
    {
        $this->attributes['quantity'] = $value;
    }

    protected $casts = [
        'quantity' => 'integer',
        'other_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'negotiated_price' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function propertyUnit()
    {
        return $this->belongsTo(ProductUnit::class, 'property_unit_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function propertyCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productSubCategory()
    {
        return $this->belongsTo(ProductSubCategory::class, 'product_sub_category_id');
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
