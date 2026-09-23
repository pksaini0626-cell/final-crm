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
            DB::statement("ALTER TABLE bookings MODIFY COLUMN booking_status ENUM('booking_generated', 'email_auth_sent', 'email_auth_done', 'ticketed', 'booking_complete', 'void', 'failed') NOT NULL DEFAULT 'booking_generated'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bookings MODIFY COLUMN booking_status ENUM('booking_generated', 'email_auth_sent', 'email_auth_done', 'ticketed', 'booking_complete', 'void') NOT NULL DEFAULT 'booking_generated'");
        }
    }
};
