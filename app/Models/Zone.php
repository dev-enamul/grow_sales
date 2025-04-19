<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Zone extends Model
{
    use HasFactory, SoftDeletes, Blameable;  
    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'slug',
        'description',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    

    public function regions()
    {
        return $this->hasMany(Region::class);
    }
}
