<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Challenge extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable;

    protected $fillable = [
        'uuid',
        'company_id',
        'title',
        'slug',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $dates = ['deleted_at'];

    protected static function booted()
    {
        static::creating(function ($challenge) {
            if (!$challenge->uuid) {
                $challenge->uuid = (string) Str::uuid();
            }
            if (!$challenge->slug && $challenge->title) {
                $challenge->slug = getSlug($challenge, $challenge->title);
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}

