<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory; 

    protected $fillable = [
        'uuid',
        'name',
        'website',
        'address',
        'logo',
        'primary_color',
        'secondary_color',
        'founded_date',
        'is_active',
        'is_verified',
        'category_id',
    ];

    public function users(){
        return $this->hasMany(User::class, 'company_id');
    }
    
    public function employees(){
        return $this->hasMany(User::class, 'company_id')->where('user_type', 'employee');
    }
    
    public function customers(){
        return $this->hasMany(User::class, 'company_id')->where('user_type', 'customer');
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
