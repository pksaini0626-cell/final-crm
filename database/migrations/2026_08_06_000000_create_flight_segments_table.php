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
        Schema::create('flight_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->integer('segment_number')->default(1);
            $table->string('flight_number')->nullable();
            $table->string('operating_carrier', 10)->nullable();
            $table->string('airline_name')->nullable();
            $table->string('airline_logo', 500)->nullable();
            $table->string('origin_airport', 10)->nullable();
            $table->string('origin_city')->nullable();
            $table->string('origin_airport_name')->nullable();
            $table->string('destination_airport', 10)->nullable();
            $table->string('destination_city')->nullable();
            $table->string('destination_airport_name')->nullable();
            $table->dateTime('departure_time')->nullable();
            $table->dateTime('arrival_time')->nullable();
            $table->string('booking_class', 10)->nullable();
            $table->string('cabin')->nullable();
            $table->string('aircraft_type')->nullable();
            $table->string('status')->nullable();
            $table->string('flight_duration')->nullable();
            $table->integer('day_offset')->default(0);
            $table->string('transit_text', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flight_segments');
    }
};
