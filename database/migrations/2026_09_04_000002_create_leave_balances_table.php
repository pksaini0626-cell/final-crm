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
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('el_balance', 6, 2)->default(0.00); // Earned Leave
            $table->decimal('cl_balance', 6, 2)->default(0.00); // Casual Leave
            $table->decimal('ul_balance', 6, 2)->default(0.00); // Unpaid Leave
            $table->string('last_accrual_month', 7)->nullable(); // e.g. '2026-09'
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
