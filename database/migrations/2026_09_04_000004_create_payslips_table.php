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
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->string('payslip_number', 50)->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('month'); // 1 - 12
            $table->unsignedSmallInteger('year'); // e.g. 2026
            $table->unsignedTinyInteger('total_days_in_month'); // e.g. 30, 31
            $table->decimal('working_days', 4, 1)->default(0.0);
            $table->decimal('el_used', 4, 1)->default(0.0);
            $table->decimal('cl_used', 4, 1)->default(0.0);
            $table->decimal('paid_days', 4, 1)->default(0.0);
            $table->decimal('unpaid_days', 4, 1)->default(0.0);
            
            // Financial details
            $table->json('earnings_data')->nullable();
            $table->decimal('gross_rate', 12, 2)->default(0.00);
            $table->decimal('gross_monthly', 12, 2)->default(0.00);
            $table->decimal('gross_arrear', 12, 2)->default(0.00);
            $table->decimal('total_earnings', 12, 2)->default(0.00);

            $table->json('deductions_data')->nullable();
            $table->decimal('total_deductions', 12, 2)->default(0.00);
            $table->decimal('net_pay', 12, 2)->default(0.00);
            $table->string('net_pay_words', 255)->nullable();

            $table->string('status', 30)->default('published'); // draft, published, paid
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['user_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
