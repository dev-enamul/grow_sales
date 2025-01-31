<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes; 

    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'slug',
        'description',
        'code',
        'product_unit_id',
        'unit_price',
        'unit',
        'regular_price',
        'sell_price',
        'vat_setting_id',
        'category_id',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ]; 
    
    protected $hidden = ['id'];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function videos()
    {
        return $this->hasMany(ProductVideo::class);
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
