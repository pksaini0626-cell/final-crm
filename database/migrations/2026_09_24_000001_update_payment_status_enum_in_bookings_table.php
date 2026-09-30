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
            DB::statement("ALTER TABLE bookings MODIFY COLUMN payment_status ENUM('pending', 'received', 'cancelled', 'cancel', 'refund', 'void', 'partial_void', 'refund_pending') NOT NULL DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bookings MODIFY COLUMN payment_status ENUM('pending', 'received', 'refund', 'cancelled') NOT NULL DEFAULT 'pending'");
        }
    }
};
