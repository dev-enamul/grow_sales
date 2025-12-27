<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use App\Traits\FindByUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SalesProduct extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable, FindByUuidTrait;

    protected $fillable = [
        'uuid',
        'company_id',
        'sales_id',
        'lead_id',
        'type',
        'property_unit_id',
        'area_id',
        'product_category_id',
        'product_sub_category_id',
        'product_id',
        'rate',
        'quantity',
        'price',
        'other_price',
        'discount',
        'vat_setting_id',
        'vat_rate',
        'vat_amount',
        'sell_price',
        'order_quantity',
        'order_price',
        'order_other_price',
        'order_discount',
        'order_total_price',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function propertyUnit()
    {
        return $this->belongsTo(\App\Models\ProductUnit::class, 'property_unit_id');
    }

    public function area()
    {
        return $this->belongsTo(\App\Models\Area::class, 'area_id');
    }

    public function productCategory()
    {
        return $this->belongsTo(\App\Models\ProductCategory::class, 'product_category_id');
    }

    public function productSubCategory()
    {
        return $this->belongsTo(\App\Models\ProductSubCategory::class, 'product_sub_category_id');
    }

    protected $casts = [
        'rate' => 'decimal:2',
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'other_price' => 'decimal:2',
        'discount' => 'float',
        'vat_rate' => 'float',
        'vat_amount' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'order_quantity' => 'integer',
        'order_price' => 'decimal:2',
        'order_other_price' => 'decimal:2',
        'order_discount' => 'float',
        'order_total_price' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function sales()
    {
        return $this->belongsTo(Sales::class);
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
