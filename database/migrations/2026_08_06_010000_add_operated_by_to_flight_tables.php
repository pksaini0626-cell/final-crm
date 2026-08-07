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
        if (Schema::hasTable('booking_flights')) {
            Schema::table('booking_flights', function (Blueprint $table) {
                if (!Schema::hasColumn('booking_flights', 'operated_by')) {
                    $table->string('operated_by')->nullable()->after('airline_name');
                }
                if (!Schema::hasColumn('booking_flights', 'operated_by_logo')) {
                    $table->string('operated_by_logo', 500)->nullable()->after('operated_by');
                }
            });
        }

        if (Schema::hasTable('flight_segments')) {
            Schema::table('flight_segments', function (Blueprint $table) {
                if (!Schema::hasColumn('flight_segments', 'operated_by')) {
                    $table->string('operated_by')->nullable()->after('airline_name');
                }
                if (!Schema::hasColumn('flight_segments', 'operated_by_logo')) {
                    $table->string('operated_by_logo', 500)->nullable()->after('operated_by');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('booking_flights')) {
            Schema::table('booking_flights', function (Blueprint $table) {
                $table->dropColumn(['operated_by', 'operated_by_logo']);
            });
        }

        if (Schema::hasTable('flight_segments')) {
            Schema::table('flight_segments', function (Blueprint $table) {
                $table->dropColumn(['operated_by', 'operated_by_logo']);
            });
        }
    }
};
