<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OtPolicy extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable;

    protected $fillable = [
        'company_id',
        'uuid',
        'designation_id',
        'is_ot_allowed',
        'ot_rate_type',
        'ot_multiplier',
        'ot_rate_fixed',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_ot_allowed' => 'boolean',
        'ot_multiplier' => 'decimal:2',
        'ot_rate_fixed' => 'decimal:2',
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
