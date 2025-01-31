<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    use HasFactory; 
    protected $fillable = [
        'user_id',
        'address_type',
        'country',
        'division',
        'district',
        'upazila_or_thana',
        'zip_code',
        'region',
        'city',
        'address',
        'is_same_present_permanent',
    ];
}
