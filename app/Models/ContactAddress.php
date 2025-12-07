<?php

namespace App\Models;

use App\Traits\FindByUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContactAddress extends Model
{
    use HasFactory, FindByUuidTrait;

    protected $fillable = [
        'uuid',
        'contact_id',
        'area_id',
        'address_type',
        'postal_code',
        'address',
        'latitude',
        'longitude',
        'is_same_present_permanent',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_same_present_permanent' => 'boolean',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

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
