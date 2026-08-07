<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_flights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->string('flight_number')->nullable();
            $table->string('operating_carrier')->nullable();
            $table->string('origin_airport', 10)->nullable();
            $table->string('destination_airport', 10)->nullable();
            $table->dateTime('departure_time')->nullable();
            $table->dateTime('arrival_time')->nullable();
            $table->string('booking_class', 5)->nullable();
            $table->string('cabin')->nullable();
            $table->string('aircraft_type')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_flights');
    }
};