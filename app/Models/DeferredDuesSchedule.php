<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeferredDuesSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'annual_billing_id',
        'customer_id',
        'year',
        'month',
        'amount',
        'status',
        'journal_entry_id',
        'recognized_at',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function annualBilling()
    {
        return $this->belongsTo(AnnualBilling::class, 'annual_billing_id');
    }
}
