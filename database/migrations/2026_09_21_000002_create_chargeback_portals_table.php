<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chargeback_portals', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Insert default portals
        $defaultPortals = ['OMT - ALERT', 'FWC - HIT', 'TOM - ALERT'];
        foreach ($defaultPortals as $portal) {
            DB::table('chargeback_portals')->insertOrIgnore([
                'name' => $portal,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chargeback_portals');
    }
};
