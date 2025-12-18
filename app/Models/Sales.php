<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use App\Traits\FindByUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Sales extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable, FindByUuidTrait;

    protected $fillable = [
        'uuid',
        'company_id',
        'customer_id',
        'lead_id',
        'organization_id',
        'campaign_id',
        'sale_type',
        'sales_by',
        'subtotal',
        'discount',
        'other_price',
        'grand_total',
        'paid',
        'due',
        'refunded',
        'transfer',
        'sale_date',
        'delivery_date',
        'return_reason',
        'return_date',
        'returned_by',
        'child_sale_id',
        'transfer_by',
        'transfer_date',
        'transfer_notes',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'other_price' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid' => 'decimal:2',
        'due' => 'decimal:2',
        'refunded' => 'decimal:2',
        'transfer' => 'decimal:2',
        'sale_date' => 'date',
        'delivery_date' => 'date',
        'return_date' => 'date',
        'transfer_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function salesBy()
    {
        return $this->belongsTo(User::class, 'sales_by');
    }

    public function returnedBy()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function transferBy()
    {
        return $this->belongsTo(User::class, 'transfer_by');
    }

    public function childSale()
    {
        return $this->belongsTo(Sales::class, 'child_sale_id');
    }

    public function parentSale()
    {
        return $this->hasOne(Sales::class, 'child_sale_id');
    }

    public function products()
    {
        return $this->hasMany(SalesProduct::class);
    }

    public function salesUsers()
    {
        return $this->hasMany(SalesUser::class, 'sales_id', 'id');
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
