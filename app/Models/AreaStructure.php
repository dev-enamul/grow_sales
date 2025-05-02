<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use App\Traits\FindByIdOrUuidTrait;
use App\Traits\FindByUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AreaStructure extends Model
{
    use HasFactory, SoftDeletes,ActionTrackable,FindByUuidTrait;

    protected $fillable = [
        'company_id',
        'uuid',
        'name',
        'parent_id',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
 
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function parent()
    {
        return $this->belongsTo(AreaStructure::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(AreaStructure::class, 'parent_id');
    }

    public function areas()
    {
        return $this->hasMany(Area::class);
    } 
}
