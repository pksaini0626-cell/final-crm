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
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'company_card_used')) {
                $table->boolean('company_card_used')->default(false)->after('total_mco');
            }
            if (!Schema::hasColumn('bookings', 'company_card_amount')) {
                $table->decimal('company_card_amount', 10, 2)->default(0.00)->after('company_card_used');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'company_card_amount')) {
                $table->dropColumn('company_card_amount');
            }
            if (Schema::hasColumn('bookings', 'company_card_used')) {
                $table->dropColumn('company_card_used');
            }
        });
    }
};
