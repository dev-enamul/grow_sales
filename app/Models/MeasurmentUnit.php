<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use App\Traits\FindByUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeasurmentUnit extends Model
{
    use HasFactory, SoftDeletes,ActionTrackable,FindByUuidTrait;  
    protected $fillable = [
        'name', 
        'uuid',
        'abbreviation', 
        'company_id', 
        'created_by', 
        'updated_by', 
        'deleted_by'
    ];
    protected $hidden = ['id'];
}
