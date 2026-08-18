<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodLock extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'is_locked',
        'locked_by',
        'locked_at',
        'reopen_reason',
    ];
}
