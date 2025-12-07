<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use App\Traits\FindByUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Organization extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable, FindByUuidTrait;

    protected $fillable = [
        'uuid',
        'primary_contact_id',
        'name',
        'slug',
        'organization_type',
        'industry',
        'website',
        'address',
        'billing_address',
        'shipping_address',
        'description',
        'logo',
        'founded_date',
        'registration_number',
        'tax_id',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'founded_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function primaryContact()
    {
        return $this->belongsTo(Contact::class, 'primary_contact_id');
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
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

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->slug) && !empty($model->name)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }
}
