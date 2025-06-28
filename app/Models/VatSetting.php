<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class VatSetting extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'name', 
        'uuid',
        'company_id', 
        'vat_percentage', 
        'is_active', 
        'note', 
        'created_by', 
        'updated_by', 
        'deleted_by'
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
