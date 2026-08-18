<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EkedcTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'meter_number',
        'transaction_reference',
        'collection_amount',
        'units_issued',
        'remittance_amount',
        'commission_amount',
        'model_type',
        'status',
        'journal_entry_id',
    ];
}
