<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Utility;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'referral_code')) {

            Schema::table('users', function (Blueprint $table) {
                $table->integer('referral_code')->default(0)->after('requested_plan');
                $table->integer('used_referral_code')->default(0)->after('referral_code');
                // $table->integer('commission_amount')->default(0)->after('used_referral_code');
            });

            Schema::table('users', function (Blueprint $table) {
                $users = User::where('type','company')->get();

                foreach ($users as $user) {
                    if (empty($user->referral_code) || ($user->referral_code) === null || ($user->referral_code) === '' || ($user->referral_code === 0)) {
                        $user->referral_code = Utility::generateReferralCode(); // You can generate your referral code as per your requirements
                        $user->save();
                    }
                }
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
