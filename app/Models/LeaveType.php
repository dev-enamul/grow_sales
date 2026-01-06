<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LeaveType extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable;

    protected $fillable = [
        'company_id',
        'uuid',
        'name',
        'is_paid',
        'days_allowed',
        'is_carry_forward',
        'max_carry_forward_days',
        'is_encashable',
        'policy_rules',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'is_carry_forward' => 'boolean',
        'is_encashable' => 'boolean',
        'policy_rules' => 'array',
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
