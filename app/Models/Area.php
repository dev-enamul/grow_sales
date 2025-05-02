<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use App\Traits\FindByIdOrUuidTrait;
use App\Traits\FindByUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable, FindByUuidTrait;
    protected $fillable = [
        'company_id',
        'uuid',
        'name',
        'parent_id',
        'area_structure_id',
        'latitude',
        'longitude',
        'radius_km',
        'polygon_coordinates',
        'google_place_id',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'polygon_coordinates' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_km' => 'float',
    ];
 
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
 
    public function areaStructure()
    {
        return $this->belongsTo(AreaStructure::class,'area_structure_id');
    }
 
    public function parent()
    {
        return $this->belongsTo(Area::class, 'parent_id');
    }
 
    public function children()
    {
        return $this->hasMany(Area::class, 'parent_id');
    }
}
