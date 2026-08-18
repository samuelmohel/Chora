<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnualBilling extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'annual_amount',
        'monthly_amount',
        'total_liable_residents',
        'total_billed_amount',
        'status',
        'approved_by',
        'posted_at',
        'created_by',
    ];

    public function deferredSchedules()
    {
        return $this->hasMany(DeferredDuesSchedule::class, 'annual_billing_id');
    }
}
