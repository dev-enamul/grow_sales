<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AttendancePolicy extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable;

    protected $fillable = [
        'company_id',
        'uuid',
        'designation_id',
        'late_days_count',
        'deduction_day_count',
        'deduction_amount_type',
        'fixed_amount',
        'is_active',
        'rules',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'deduction_day_count' => 'decimal:2',
        'fixed_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'rules' => 'array',
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

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }
}
