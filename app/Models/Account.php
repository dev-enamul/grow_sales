<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\ActionTrackable;
use App\Traits\FindByUuidTrait;

class Account extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable, FindByUuidTrait;

    protected $fillable = [
        'company_id',
        'uuid',
        'code',
        'name',
        'type', // Asset, Liability, Equity, Income, Expense
        'parent_id',
        'opening_balance',
        'opening_balance_date',
        'is_bank_account',
        'bank_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'opening_balance_date' => 'date',
        'is_bank_account' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function debitTransactions()
    {
        return $this->hasMany(Transaction::class, 'debit_account_id');
    }

    public function creditTransactions()
    {
        return $this->hasMany(Transaction::class, 'credit_account_id');
    }

     public function getBalanceAttribute()
    {
        $debit = $this->debitTransactions()->sum('debit');
        $credit = $this->creditTransactions()->sum('credit');

        if (in_array($this->type, ['Asset', 'Expense'])) {
            return ($this->opening_balance + $debit) - $credit;
        } 
        return ($this->opening_balance + $credit) - $debit; 
    }
}
