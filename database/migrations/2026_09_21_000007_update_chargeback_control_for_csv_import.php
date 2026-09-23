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
        Schema::table('chargeback_control', function (Blueprint $table) {
            // Widen card_no to store full masked card strings like 528072******1997
            $table->string('card_no', 50)->nullable()->change();

            // Allow current_status to be nullable or default
            $table->string('current_status', 255)->nullable()->default('Chargeback received')->change();

            // Allow shift_month and statement_month to be flexible strings (e.g. "Jan--23", "--")
            $table->string('shift_month', 50)->nullable()->change();
            $table->string('statement_month', 50)->nullable()->change();

            // Add remarks and passenger columns if not already present
            if (!Schema::hasColumn('chargeback_control', 'remarks')) {
                $table->text('remarks')->nullable()->after('vertical');
            }
            if (!Schema::hasColumn('chargeback_control', 'passenger')) {
                $table->string('passenger', 255)->nullable()->after('remarks');
            }

            // Index case_number for fast deduplication lookups
            $table->index('case_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chargeback_control', function (Blueprint $table) {
            $table->dropIndex(['case_number']);
            if (Schema::hasColumn('chargeback_control', 'passenger')) {
                $table->dropColumn('passenger');
            }
            if (Schema::hasColumn('chargeback_control', 'remarks')) {
                $table->dropColumn('remarks');
            }
            $table->string('card_no', 10)->nullable()->change();
            $table->date('shift_month')->nullable()->change();
            $table->date('statement_month')->nullable()->change();
        });
    }
};
