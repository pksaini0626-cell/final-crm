<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_id', 7)->unique(); // Auto-generated 7-character alphanumeric
            $table->foreignId('agent_id')->constrained('users')->onDelete('cascade');
            
            $table->date('booking_date');
            $table->string('call_type')->nullable();
            $table->string('vertical')->default('flight');
            $table->string('service_provided'); // new_booking, cancellation, baggage_addition, etc.
            $table->string('booking_portal')->default('website'); // website, gds, amadeus, galileo
            
            // PNR Details
            $table->string('gk_pnr')->nullable();
            $table->string('airline_pnr')->nullable();
            $table->string('airline_name')->nullable();
            $table->string('airline_code', 10)->nullable();
            
            // Route Summary
            $table->string('from_airport', 10)->nullable();
            $table->string('to_airport', 10)->nullable();
            $table->string('from_city')->nullable();
            $table->string('to_city')->nullable();
            $table->date('travel_date')->nullable();
            $table->string('language')->default('English');
            
            // Billing & Customer Details
            $table->string('card_holder_name')->nullable();
            $table->string('calling_number')->nullable();
            $table->string('billing_phone')->nullable();
            $table->string('card_last_4', 4)->nullable();
            $table->string('email_address')->nullable();
            
            // Status Tracking
            $table->enum('booking_status', [
                'booking_generated', 
                'email_auth_sent', 
                'email_auth_done', 
                'ticketed', 
                'booking_complete', 
                'void'
            ])->default('booking_generated');
            
            $table->enum('case_status', [
                'rdr', 
                'retrieval', 
                'chargeback', 
                'refund', 
                'void'
            ])->nullable(); // Populated if booking_status is void
            
            $table->boolean('email_auth_taken')->default(false);
            
            // Financials
            $table->string('currency', 3)->default('USD');
            $table->string('merchant')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->decimal('paid_to_airline', 10, 2)->default(0.00);
            $table->decimal('paid_to_merchant', 10, 2)->default(0.00);
            $table->decimal('total_mco', 10, 2)->default(0.00);
            
            $table->enum('payment_status', [
                'pending', 
                'received', 
                'refund', 
                'cancelled'
            ])->default('pending');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};