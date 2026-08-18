<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestrictedProgrammeFund extends Model
{
    use HasFactory;

    protected $fillable = [
        'programme_code',
        'programme_name',
        'donor_name',
        'total_received',
        'total_spent',
        'available_balance',
        'status',
        'created_by',
    ];
}
