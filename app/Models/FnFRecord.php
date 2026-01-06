<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class FnFRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fnf_records';

    protected $fillable = [
        'uuid',
        'resignation_id',
        'total_payable',
        'total_deduction',
        'net_amount',
        'is_settled',
        'settlement_date',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'total_payable' => 'decimal:2',
        'total_deduction' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'is_settled' => 'boolean',
        'settlement_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function resignation()
    {
        return $this->belongsTo(Resignation::class);
    }
}
