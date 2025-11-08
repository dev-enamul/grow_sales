<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use App\Traits\FindByUuidTrait;
use App\Traits\PaginatorTrait; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductCategory extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable, PaginatorTrait, FindByUuidTrait;
    protected $fillable = [
        'uuid',
        'company_id',
        'category_type_id',
        'measurment_unit_id',
        'area_id',
        'name',
        'slug',
        'description',
        'progress_stage',
        'ready_date',
        'address',
        'status',
        'applies_to',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
 
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function categoryType()
    {
        return $this->belongsTo(CategoryType::class);
    }

    public function measurmentUnit()
    {
        return $this->belongsTo(MeasurmentUnit::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
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
