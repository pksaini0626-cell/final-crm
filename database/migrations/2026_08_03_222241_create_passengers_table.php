<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->string('pax_index', 10)->nullable(); // e.g., P1, P2
            $table->string('first_name');
            $table->string('last_name');
            $table->string('title')->nullable(); // MR, MS, MRS, CHILD
            $table->string('ticket_number')->nullable(); // Updated later by agent/manager
            $table->string('seat_number')->nullable();   // Updated later by agent/manager
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passengers');
    }
};