<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetainerProduct extends Model
{
    protected $fillable = [
        'product_id',
        'retainer_id',
        'quantity',
        'tax',
        'discount',
        'total',
    ];

    public function product()
    {
        return $this->hasOne('App\Models\ProductService', 'id', 'product_id');
    }

    public function tax($taxes)
    {
        $taxArr = explode(',', $taxes);

        $taxes = [];
        // foreach($taxArr as $tax)
        // {
        //     $taxes[] = TaxRate::find($tax);
        // }

        return $taxes;
    }
    
}
