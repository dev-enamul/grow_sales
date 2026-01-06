<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PfPolicy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'designation_id',
        'employee_contribution_percent',
        'company_contribution_percent',
        'calculation_on',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'employee_contribution_percent' => 'decimal:2',
        'company_contribution_percent' => 'decimal:2',
        'is_active' => 'boolean',
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
