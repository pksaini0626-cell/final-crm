<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_remarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('remark');
            $table->enum('type', ['agent_remark', 'admin_remark', 'system_log'])->default('agent_remark');
            $table->timestamps(); // Provides automated timestamp
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_remarks');
    }
};