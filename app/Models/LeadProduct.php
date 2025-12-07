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
        'qty',
        'price',
        'subtotal',
        'vat_rate',
        'vat_value',
        'discount',
        'grand_total',
        'negotiated_price',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'qty' => 'integer',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'vat_rate' => 'float',
        'vat_value' => 'decimal:2',
        'discount' => 'decimal:2',
        'grand_total' => 'decimal:2',
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
