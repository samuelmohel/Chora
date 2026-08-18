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
        if (!Schema::hasColumn('users', 'trial_plan', 'trial_expire_date')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('trial_plan')->nullable()->after('storage_limit');
                $table->string('trial_expire_date')->default(0)->after('trial_plan');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
