<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductService extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'sale_price',
        'purchase_price',
        'tax_id',
        'category_id',
        'unit_id',
        'type',
        'sale_chartaccount_id',
        'expense_chartaccount_id',
        'created_by',
    ];

    public function taxes()
    {
        return $this->hasOne('App\Models\Tax', 'id', 'tax_id');
    }

    public function unit()
    {
        return $this->hasOne('App\Models\ProductServiceUnit', 'id', 'unit_id');
    }

    public function category()
    {
        return $this->hasOne('App\Models\ProductServiceCategory', 'id', 'category_id');
    }

    public function tax($taxes)
    {
        $taxArr = explode(',', $taxes);

        $taxes  = [];
        foreach ($taxArr as $tax) {
            $taxes[] = Tax::find($tax);
        }

        return $taxes;
    }

    public function taxRate($taxes)
    {
        $taxArr  = explode(',', $taxes);
        $taxRate = 0;
        foreach ($taxArr as $tax) {
            $tax     = Tax::find($tax);
            $taxRate += $tax->rate;
        }

        return $taxRate;
    }

    public static function taxData($taxes)
    {
        // dd($taxes);
        $taxArr = explode(',', $taxes);
        $taxes = [];

        foreach ($taxArr as $tax) {
            $taxesData = Tax::find($tax);
            $taxes[]   = !empty($taxesData) ? $taxesData->name : '';
        }
        return implode(',', $taxes);
    }

    public static function Taxe($taxe)
    {
        $categoryArr  = explode(',', $taxe);
        $taxeRate = 0;
        // dd($taxeRate);
        foreach ($categoryArr as $taxe) {
            $taxe    = Tax::find($taxe);

            $taxeRate        = isset($taxe) ? $taxe->name : '';
        }
        return $taxeRate;
    }

    public static function productserviceunit($unit)
    {
        $categoryArr  = explode(',', $unit);
        $unitRate = 0;
        foreach ($categoryArr as $unit) {
            $unit    = ProductServiceUnit::find($unit);
            $unitRate        = isset($unit) ? $unit->name : '';
        }

        return $unitRate;
    }

    public static function productcategory($category)
    {
        $categoryArr  = explode(',', $category);
        $categoryRate = 0;
        foreach ($categoryArr as $category) {
            $category    = ProductServiceCategory::find($category);
            $categoryRate        = isset($category) ? $category->name : '';
        }

        return $categoryRate;
    }

    public static function getallproducts()
    {
        return ProductService::select('product_services.*', 'c.name as categoryname')
            ->where('product_services.type', '=', 'product')
            ->leftjoin('product_service_categories as c', 'c.id', '=', 'product_services.category_id')
            ->where('product_services.created_by', '=', Auth::user()->creatorId())
            ->orderBy('product_services.id', 'DESC');
    }
}