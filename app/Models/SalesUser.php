<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesUser extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable;

    protected $fillable = [
        'sales_id',
        'user_id',
        'commission_type',
        'commission_value',
        'commission',
        'payable_commission',
        'commission_payment_type',
        'paid_commission',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'commission_value' => 'decimal:2',
        'commission' => 'decimal:2',
        'payable_commission' => 'decimal:2',
        'paid_commission' => 'decimal:2',
    ];

    // Relationships
    public function sales()
    {
        return $this->belongsTo(Sales::class, 'sales_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
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

    /**
     * Calculate payable commission based on paid amount ratio.
     *
     * @param float $grandTotal
     * @param float $paidAmount
     * @return float
     */
    public function calculatePayable($grandTotal, $paidAmount)
    {
        if ($grandTotal <= 0) {
            return 0;
        }
        return ($this->commission / $grandTotal) * $paidAmount;
    }
}
