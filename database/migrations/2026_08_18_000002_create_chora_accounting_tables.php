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
        // 1. Annual Billings Table
        if (!Schema::hasTable('annual_billings')) {
            Schema::create('annual_billings', function (Blueprint $table) {
                $table->id();
                $table->integer('year');
                $table->decimal('annual_amount', 15, 2)->default(96000.00);
                $table->decimal('monthly_amount', 15, 2)->default(8000.00);
                $table->integer('total_liable_residents')->default(0);
                $table->decimal('total_billed_amount', 15, 2)->default(0.00);
                $table->enum('status', ['draft', 'approved', 'posted'])->default('draft');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->unsignedBigInteger('created_by');
                $table->timestamps();
            });
        }

        // 2. Deferred Dues Schedules Table
        if (!Schema::hasTable('deferred_dues_schedules')) {
            Schema::create('deferred_dues_schedules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('annual_billing_id')->nullable();
                $table->unsignedBigInteger('customer_id');
                $table->integer('year');
                $table->integer('month'); // 1 - 12
                $table->decimal('amount', 15, 2)->default(8000.00);
                $table->enum('status', ['pending', 'recognized'])->default('pending');
                $table->unsignedBigInteger('journal_entry_id')->nullable();
                $table->timestamp('recognized_at')->nullable();
                $table->timestamps();
            });
        }

        // 3. Bank Reconciliations Table
        if (!Schema::hasTable('bank_reconciliations')) {
            Schema::create('bank_reconciliations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bank_account_id');
                $table->date('statement_date');
                $table->decimal('statement_ending_balance', 15, 2);
                $table->decimal('book_ending_balance', 15, 2);
                $table->decimal('reconciled_balance', 15, 2);
                $table->decimal('difference', 15, 2)->default(0.00);
                $table->enum('status', ['draft', 'in_review', 'completed'])->default('draft');
                $table->unsignedBigInteger('prepared_by');
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('signed_off_at')->nullable();
                $table->timestamps();
            });
        }

        // 4. Bank Reconciliation Items Table
        if (!Schema::hasTable('bank_reconciliation_items')) {
            Schema::create('bank_reconciliation_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bank_reconciliation_id');
                $table->date('transaction_date');
                $table->string('reference')->nullable();
                $table->string('description');
                $table->decimal('amount', 15, 2);
                $table->enum('type', ['deposit', 'withdrawal', 'bank_charge', 'unidentified']);
                $table->enum('match_status', ['unmatched', 'matched', 'adjusted'])->default('unmatched');
                $table->unsignedBigInteger('journal_entry_id')->nullable();
                $table->timestamps();
            });
        }

        // 5. Restricted Programme Funds Table
        if (!Schema::hasTable('restricted_programme_funds')) {
            Schema::create('restricted_programme_funds', function (Blueprint $table) {
                $table->id();
                $table->string('programme_code');
                $table->string('programme_name');
                $table->string('donor_name')->nullable();
                $table->decimal('total_received', 15, 2)->default(0.00);
                $table->decimal('total_spent', 15, 2)->default(0.00);
                $table->decimal('available_balance', 15, 2)->default(0.00);
                $table->enum('status', ['active', 'closed'])->default('active');
                $table->unsignedBigInteger('created_by');
                $table->timestamps();
            });
        }

        // 6. EKEDC Transactions Table
        if (!Schema::hasTable('ekedc_transactions')) {
            Schema::create('ekedc_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('meter_number');
                $table->string('transaction_reference')->unique();
                $table->decimal('collection_amount', 15, 2);
                $table->decimal('units_issued', 10, 2)->default(0.00);
                $table->decimal('remittance_amount', 15, 2)->default(0.00);
                $table->decimal('commission_amount', 15, 2)->default(0.00);
                $table->enum('model_type', ['agent', 'principal'])->default('agent');
                $table->enum('status', ['pending', 'collected', 'settled', 'refunded'])->default('collected');
                $table->unsignedBigInteger('journal_entry_id')->nullable();
                $table->timestamps();
            });
        }

        // 7. Dog Control Fines & Reconnection Fees Tables
        if (!Schema::hasTable('dog_control_fines')) {
            Schema::create('dog_control_fines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id');
                $table->string('incident_reference');
                $table->text('incident_description')->nullable();
                $table->decimal('fine_amount', 15, 2)->default(20000.00);
                $table->date('incident_date');
                $table->date('due_date');
                $table->enum('status', ['pending_approval', 'approved', 'paid', 'appealed', 'waived'])->default('approved');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->unsignedBigInteger('journal_entry_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('reconnection_fees')) {
            Schema::create('reconnection_fees', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id');
                $table->string('reference');
                $table->decimal('fee_amount', 15, 2)->default(10000.00);
                $table->date('disconnection_date');
                $table->date('reconnection_date')->nullable();
                $table->enum('status', ['disconnected', 'prepaid', 'reconnected'])->default('disconnected');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->unsignedBigInteger('journal_entry_id')->nullable();
                $table->timestamps();
            });
        }

        // 8. Period Locks Table
        if (!Schema::hasTable('period_locks')) {
            Schema::create('period_locks', function (Blueprint $table) {
                $table->id();
                $table->integer('year');
                $table->integer('month');
                $table->boolean('is_locked')->default(true);
                $table->unsignedBigInteger('locked_by');
                $table->timestamp('locked_at');
                $table->text('reopen_reason')->nullable();
                $table->timestamps();
            });
        }

        // 9. Journal Entries Extensions for Reversals & Approvals
        Schema::table('journal_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('journal_entries', 'is_reversed')) {
                $table->boolean('is_reversed')->default(false)->after('journal_id');
            }
            if (!Schema::hasColumn('journal_entries', 'reversed_by_id')) {
                $table->unsignedBigInteger('reversed_by_id')->nullable()->after('is_reversed');
            }
            if (!Schema::hasColumn('journal_entries', 'reverses_id')) {
                $table->unsignedBigInteger('reverses_id')->nullable()->after('reversed_by_id');
            }
            if (!Schema::hasColumn('journal_entries', 'reversal_reason')) {
                $table->string('reversal_reason')->nullable()->after('reverses_id');
            }
            if (!Schema::hasColumn('journal_entries', 'approval_status')) {
                $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved')->after('reversal_reason');
            }
            if (!Schema::hasColumn('journal_entries', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('approval_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconnection_fees');
        Schema::dropIfExists('dog_control_fines');
        Schema::dropIfExists('ekedc_transactions');
        Schema::dropIfExists('restricted_programme_funds');
        Schema::dropIfExists('bank_reconciliation_items');
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('deferred_dues_schedules');
        Schema::dropIfExists('annual_billings');
        Schema::dropIfExists('period_locks');
    }
};
