<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use App\Traits\FindByUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoryType extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable,FindByUuidTrait;

    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'is_active',
        'applies_to',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
 
    
}
