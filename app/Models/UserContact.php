<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserContact extends Model
{
    use HasFactory, SoftDeletes; 
    protected $fillable = [
        'user_id',
        'name',
        'relationship_or_role',
        'office_phone',
        'personal_phone',
        'office_email',
        'personal_email',
        'website',
        'whatsapp',
        'imo',
        'facebook',
        'linkedin',
        'created_by',
        'updated_by',
        'deleted_by'
    ];
}
