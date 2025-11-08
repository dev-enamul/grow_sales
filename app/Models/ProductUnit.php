<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use App\Traits\FindByUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductUnit extends Model
{
    use HasFactory, SoftDeletes,FindByUuidTrait,ActionTrackable;  
    protected $fillable = [
        'uuid',
        'name',
        'abbreviation',
        'company_id',
        'applies_to',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by'
    ]; 

    
}
