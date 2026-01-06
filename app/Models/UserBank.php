<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBank extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bank_name',
        'branch_name',
        'account_name',
        'account_number',
        'routing_number',
        'account_type',
        'is_primary'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'account_number' => 'encrypted',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
