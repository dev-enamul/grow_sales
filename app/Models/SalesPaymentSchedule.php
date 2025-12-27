<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use App\Traits\FindByUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SalesPaymentSchedule extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable, FindByUuidTrait;

     protected $fillable = [
        'company_id',
        'uuid',
        'sales_id',
        'payment_reason_id',
        'amount',
        'due_date',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    // Relations
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function sales()
    {
        return $this->belongsTo(Sales::class, 'sales_id');
    }

    public function sale()
    {
        return $this->sales();
    }

    public function paymentReason()
    {
        return $this->belongsTo(PaymentReason::class);
    }

    public function payments()
    {
        return $this->hasMany(SalesPayment::class, 'payment_schedule_id');
    }

    // Accessors for partial payment
    public function getPaidAmountAttribute()
    {
        return $this->payments()->sum('amount');
    }

    public function getRemainingAmountAttribute()
    {
        return $this->amount - $this->paidAmount;
    }

    public function getIsFullyPaidAttribute()
    {
        return $this->remainingAmount <= 0;
    }

    public function getIsPartiallyPaidAttribute()
    {
        return $this->paidAmount > 0 && $this->remainingAmount > 0;
    }

    public function getStatusAttribute()
    {
        if ($this->is_fully_paid) {
            return 'Paid';
        }

        $today = now()->startOfDay();
        $dueDate = \Carbon\Carbon::parse($this->due_date)->startOfDay();
        
        $overdueThreshold = $dueDate->copy()->addMonth();

        if ($today->gt($overdueThreshold)) {
            return 'Overdue'; 
        }

        if ($today->gt($dueDate)) {
            return 'Due'; 
        }

        if ($this->paidAmount == 0) {
            return 'Unpaid';
        }

        return 'Partially Paid';
    }

     
}
