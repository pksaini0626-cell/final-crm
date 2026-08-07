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
        Schema::create('change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('agent_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_changes_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('change_request_text');
            $table->text('agent_remark')->nullable();
            $table->text('changes_remark')->nullable();
            $table->enum('status', ['pending', 'working', 'completed', 'cancelled'])->default('pending');
            $table->dateTime('assigned_at');
            $table->dateTime('working_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('change_requests');
    }
};
