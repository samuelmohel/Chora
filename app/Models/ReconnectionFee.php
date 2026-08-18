<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReconnectionFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'reference',
        'fee_amount',
        'disconnection_date',
        'reconnection_date',
        'status',
        'approved_by',
        'journal_entry_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
