<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use App\Traits\FindByUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Bank extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable, FindByUuidTrait;

     protected $fillable = [
        'company_id',
        'uuid',
        'name',
        'logo',
        'account_number',
        'account_holder',
        'branch',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relations
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function logoFile()
    {
        return $this->belongsTo(File::class, 'logo');
    }

     

}
