<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Affiliate extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'user_id',
        'affiliate_id',
        'signature',
        'referred_by',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public static function generateNextAffiliateId(){
        $largest_affiliate_id = Affiliate::where('affiliate_id', 'like', 'AFF-%') 
        ->pluck('affiliate_id')
                ->map(function ($id) {
                        return preg_replace("/[^0-9]/", "", $id);
                }) 
        ->max(); 
        $largest_affiliate_id++;
        $new_affiliate_id = 'AFF-' . str_pad($largest_affiliate_id, 6, '0', STR_PAD_LEFT);
        return $new_affiliate_id;
    } 

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
