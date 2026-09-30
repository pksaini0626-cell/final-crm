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
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('agent_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Request Type: void, partial_void, refund
            $table->enum('request_type', ['void', 'partial_void', 'refund']);
            $table->decimal('refund_amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('refund_date');
            $table->string('reason_for_refund');
            $table->text('remarks'); // mandatory
            
            // MIS & Admin Remarks and Audit Fields
            $table->text('mis_remarks')->nullable();
            $table->text('admin_remarks')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('deduction_from_agent')->default('nil');
            $table->string('email_sent_to_agent_by')->default('nil');
            $table->string('receipt_sent_to_cs')->default('nil');
            $table->string('is_duplicate')->default('nil');
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();

            $table->index(['booking_id', 'status']);
            $table->index(['request_type', 'status']);
            $table->index('refund_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};
