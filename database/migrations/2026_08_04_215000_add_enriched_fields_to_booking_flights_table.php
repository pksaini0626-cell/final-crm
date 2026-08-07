<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_flights', function (Blueprint $table) {
            $table->string('airline_name')->nullable()->after('operating_carrier');
            $table->string('airline_logo')->nullable()->after('airline_name');
            $table->string('status')->nullable()->after('aircraft_type');
            $table->string('origin_city')->nullable()->after('origin_airport');
            $table->string('origin_airport_name')->nullable()->after('origin_city');
            $table->string('destination_city')->nullable()->after('destination_airport');
            $table->string('destination_airport_name')->nullable()->after('destination_city');
            $table->string('flight_duration')->nullable()->after('arrival_time');
            $table->integer('day_offset')->default(0)->after('flight_duration');
            $table->string('transit_text')->nullable()->after('day_offset');
        });
    }

    public function down(): void
    {
        Schema::table('booking_flights', function (Blueprint $table) {
            $table->dropColumn([
                'airline_name',
                'airline_logo',
                'status',
                'origin_city',
                'origin_airport_name',
                'destination_city',
                'destination_airport_name',
                'flight_duration',
                'day_offset',
                'transit_text',
            ]);
        });
    }
};
