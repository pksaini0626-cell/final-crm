<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE chargeback_control MODIFY COLUMN deadline_date DATE NULL DEFAULT NULL");
            DB::statement("ALTER TABLE chargeback_control MODIFY COLUMN action_taken_date DATE NULL DEFAULT NULL");
        }

        Schema::table('chargeback_control', function (Blueprint $table) {
            if (!Schema::hasColumn('chargeback_control', 'attachments')) {
                $table->json('attachments')->nullable()->after('reason_description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chargeback_control', function (Blueprint $table) {
            if (Schema::hasColumn('chargeback_control', 'attachments')) {
                $table->dropColumn('attachments');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE chargeback_control MODIFY COLUMN deadline_date DATE NOT NULL");
            DB::statement("ALTER TABLE chargeback_control MODIFY COLUMN action_taken_date DATE NOT NULL");
        }
    }
};
