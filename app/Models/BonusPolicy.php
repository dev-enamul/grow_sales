<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BonusPolicy extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable;

    protected $table = 'bonus_policies';

    protected $fillable = [
        'uuid',
        'company_id',
        'title',
        'type',      // fixed, percentage
        'value',
        'basis',     // basic, gross
        'min_service_period_months',
        'religion',
        'gender',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_service_period_months' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
