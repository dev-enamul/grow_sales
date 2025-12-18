<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use App\Traits\FindByUuidTrait;
use App\Traits\PaginatorTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable, PaginatorTrait, FindByUuidTrait;

    protected $fillable = [
        'uuid',
        'company_id',
        'product_unit_id',
        'measurment_unit_id',
        'category_id',
        'sub_category_id',
        'name',
        'slug',
        'description',
        'code',
        'rate',
        'quantity',
        'price',
        'other_price',
        'discount',
        'vat_setting_id',
        'vat_rate',
        'vat_amount',
        'sell_price',
        'image',
        'qty_in_stock',
        'floor',
        'status',
        'applies_to',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'other_price' => 'decimal:2',
        'discount' => 'float',
        'vat_rate' => 'float',
        'vat_amount' => 'decimal:2',
        'sell_price' => 'decimal:2',
    ];

 
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
 
    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class);
    }

    public function measurmentUnit()
    {
        return $this->belongsTo(MeasurmentUnit::class);
    }
 
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }
 
    public function subCategory()
    {
        return $this->belongsTo(ProductSubCategory::class, 'sub_category_id');
    }
 
    public function vatSetting()
    {
        return $this->belongsTo(VatSetting::class, 'vat_setting_id');
    }
 
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }  

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
 
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

}
