<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
{
    use HasFactory, SoftDeletes; 

    protected $fillable = [
        'zone_id',
        'name',
        'slug',
        'description',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    } 

    public function area()
    {
        return $this->hasMany(Area::class);
    }
}
