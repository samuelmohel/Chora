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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'property_account_id')) {
                $table->string('property_account_id')->nullable()->after('customer_id');
            }
            if (!Schema::hasColumn('customers', 'occupancy_status')) {
                $table->enum('occupancy_status', ['occupied', 'vacant_owner_liable', 'vacant_exempt', 'new_prorated', 'closed'])->default('occupied')->after('is_occupied');
            }
            if (!Schema::hasColumn('customers', 'billing_status')) {
                $table->enum('billing_status', ['active', 'suspended', 'exempt'])->default('active')->after('occupancy_status');
            }
            if (!Schema::hasColumn('customers', 'exemption_reason')) {
                $table->string('exemption_reason')->nullable()->after('billing_status');
            }
            if (!Schema::hasColumn('customers', 'exemption_approved_by')) {
                $table->unsignedBigInteger('exemption_approved_by')->nullable()->after('exemption_reason');
            }
            if (!Schema::hasColumn('customers', 'effective_date')) {
                $table->date('effective_date')->nullable()->after('exemption_approved_by');
            }
            if (!Schema::hasColumn('customers', 'meter_number')) {
                $table->string('meter_number')->nullable()->after('effective_date');
            }
            if (!Schema::hasColumn('customers', 'vending_id')) {
                $table->string('vending_id')->nullable()->after('meter_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'property_account_id',
                'occupancy_status',
                'billing_status',
                'exemption_reason',
                'exemption_approved_by',
                'effective_date',
                'meter_number',
                'vending_id',
            ]);
        });
    }
};
