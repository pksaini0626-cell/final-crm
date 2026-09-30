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
        Schema::create('chargeback_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('chargeback_id')->nullable()->constrained('chargeback_control')->nullOnDelete();
            $table->string('case_number')->nullable()->index();
            $table->string('pnr', 50)->nullable()->index();
            $table->string('action'); // 'login', 'created', 'updated', 'deleted', 'remark_added', 'csv_imported'
            $table->text('description')->nullable();
            $table->json('changes')->nullable(); // Store field-level diffs ['field' => ['old' => ..., 'new' => ...]]
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chargeback_activities');
    }
};
