<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory; 

    protected $fillable = [
        'uuid',
        'name',
        'website',
        'address',
        'logo',
        'primary_color',
        'secondary_color',
        'founded_date',
        'is_active',
        'category_id',
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
