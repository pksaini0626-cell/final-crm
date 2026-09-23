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
        Schema::create('chargeback_control', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->string('booking_reference', 20)->nullable();
            
            // Portal & Case Identification
            $table->string('portal');
            $table->string('case_number');
            $table->enum('case_type', ['new', 'old'])->default('new');
            $table->string('dispute_type'); // alert, chargeback, rdr, retrievel
            
            // Dates
            $table->date('received_date');
            $table->string('received_month'); // e.g. 2026-09
            $table->date('booking_date')->nullable();
            $table->string('booking_month')->nullable(); // e.g. 2026-08
            $table->date('deadline_date');
            $table->date('action_taken_date');
            
            // Status Tracking
            $table->string('cbk_status'); // Represent, Accepted, Declined, RDR-Lost, Recharge, reversed, refunded, proceed with chargeback
            $table->string('current_status'); // Proceed with chargeback, Won, Lost, Refunded, Voided, Chargeback received
            
            // Booking Details Fetched
            $table->string('pnr')->nullable();
            $table->string('agent_name')->nullable();
            $table->string('currency', 10)->default('USD');
            $table->decimal('total_booking_amount', 10, 2)->default(0.00);
            $table->decimal('disputed_amount', 10, 2)->default(0.00);
            
            // Card Details
            $table->string('cc_brand')->nullable();
            $table->string('card_no', 10)->nullable();
            
            // Reason & Service
            $table->string('reason_code')->nullable();
            $table->text('reason_description')->nullable();
            $table->string('vertical')->default('Flight');
            $table->string('service_provided')->nullable();
            
            // Shift & Operational Fields
            $table->time('shift_time')->nullable();
            $table->date('shift_month')->nullable();
            $table->date('statement_month')->nullable();
            $table->integer('sds')->default(1);
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chargeback_control');
    }
};
