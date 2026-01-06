<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class HrSetting extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable;

    protected $fillable = [
        'company_id',
        'uuid',
        'weekend_type',
        'salary_deduct_on_absent',
        'absent_fine_type',
        'absent_fine_amount',
        'late_fine_enabled',
        'config',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'salary_deduct_on_absent' => 'boolean',
        'late_fine_enabled' => 'boolean',
        'absent_fine_amount' => 'decimal:2',
        'config' => 'array',
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
}
