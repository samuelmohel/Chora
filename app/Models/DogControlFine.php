<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DogControlFine extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'incident_reference',
        'incident_description',
        'fine_amount',
        'incident_date',
        'due_date',
        'status',
        'approved_by',
        'journal_entry_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
