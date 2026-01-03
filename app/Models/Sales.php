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
        'sale_id',
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
        'primary_contact_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'approved_by',
    ];

    public function keyContact()
    {
        return $this->belongsTo(Contact::class, 'key_contact_id');
    }

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'other_price' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'refunded' => 'decimal:2',
        'transfer' => 'decimal:2',
        'sale_date' => 'date',
        'delivery_date' => 'date',
        'return_date' => 'date',
        'transfer_date' => 'date',
    ];

    protected $appends = ['paid_amount', 'due_amount'];

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

    public function payments()
    {
        return $this->hasMany(SalesPayment::class, 'sales_id');
    }

    public function getPaidAmountAttribute()
    {
        // Assuming status 1 is Approved
        return $this->payments()->where('status', 1)->sum('amount');
    }

    public function getDueAmountAttribute()
    {
        return $this->grand_total - $this->paid_amount;
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

    public static function generateNextSaleId($companyId = null) {
        // If company_id is not provided, try to get it from auth user
        if (!$companyId && auth()->check()) {
            $companyId = auth()->user()->company_id;
        }

        // Build query with company filter if available
        // Use withTrashed() to include soft deleted records in the check
        $query = self::withTrashed()->where('sale_id', 'like', 'SLS-%');
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $largest_sale_id = $query->pluck('sale_id')
                ->map(function ($id) {
                        return (int) preg_replace("/[^0-9]/", "", $id);
                })
        ->max();

        // Handle case where no sales exist yet
        $largest_sale_id = $largest_sale_id ? $largest_sale_id : 0;
        $largest_sale_id++;

        $new_sale_id = 'SLS-' . str_pad($largest_sale_id, 6, '0', STR_PAD_LEFT);

        // Check if the generated ID already exists (including soft deleted - race condition protection)
        $maxAttempts = 10;
        $attempt = 0;
        while ($attempt < $maxAttempts) {
            $exists = self::withTrashed()->where('sale_id', $new_sale_id);
            if ($companyId) {
                $exists->where('company_id', $companyId);
            }
            $exists = $exists->exists();

            if (!$exists) {
                return $new_sale_id;
            }

            // If exists, try next number
            $largest_sale_id++;
            $new_sale_id = 'SLS-' . str_pad($largest_sale_id, 6, '0', STR_PAD_LEFT);
            $attempt++;
        }

        // Fallback: add timestamp to ensure uniqueness
        return 'SLS-' . str_pad($largest_sale_id, 6, '0', STR_PAD_LEFT) . '-' . time();
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
