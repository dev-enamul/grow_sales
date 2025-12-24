<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use App\Traits\FindByUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SalesPayment extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable, FindByUuidTrait;

    protected $fillable = [
        'company_id',
        'uuid',
        'sales_id',
        'payment_schedule_id',
        'bank_id',
        'payment_reason_id',
        'transaction_id',
        'amount',
        'payment_date',
        'transaction_ref',
        'notes',
        'status', // 0=Pending, 1=Approved, 2=Rejected
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    // Relations
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sales_id');
    }

    public function schedule()
    {
        return $this->belongsTo(SalesPaymentSchedule::class, 'payment_schedule_id');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function paymentReason()
    {
        return $this->belongsTo(PaymentReason::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
 
 
}
