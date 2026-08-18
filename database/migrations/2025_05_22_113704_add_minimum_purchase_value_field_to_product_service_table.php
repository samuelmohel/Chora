<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_services', function (Blueprint $table) {
            //
            $table->decimal('minimum_purchase',15,2)->default('0.00');
            $table->string('price_type')->default('fixed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_service', function (Blueprint $table) {
            //
            $table->dropColumn('minimum_purchase');
            $table->dropColumn('price_ty');
        });
    }
};
