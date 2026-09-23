<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE chargeback_control MODIFY COLUMN cbk_status VARCHAR(255) NULL DEFAULT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE chargeback_control MODIFY COLUMN cbk_status VARCHAR(255) NOT NULL");
        }
    }
};
