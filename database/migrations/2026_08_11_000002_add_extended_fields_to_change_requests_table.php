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
        Schema::table('change_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('change_requests', 'request_type')) {
                $table->string('request_type', 150)->nullable()->after('assigned_changes_user_id');
            }
            if (!Schema::hasColumn('change_requests', 'paid_to_airline')) {
                $table->string('paid_to_airline', 10)->nullable()->after('changes_remark');
            }
            if (!Schema::hasColumn('change_requests', 'fop')) {
                $table->string('fop', 150)->nullable()->after('paid_to_airline');
            }
            if (!Schema::hasColumn('change_requests', 'final_remark')) {
                $table->text('final_remark')->nullable()->after('fop');
            }
            if (!Schema::hasColumn('change_requests', 'closed_by_user_id')) {
                $table->foreignId('closed_by_user_id')->nullable()->after('final_remark')->constrained('users')->onDelete('set null');
            }
        });

        // Change status column to string to allow all custom statuses
        Schema::table('change_requests', function (Blueprint $table) {
            $table->string('status', 50)->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            if (Schema::hasColumn('change_requests', 'closed_by_user_id')) {
                $table->dropForeign(['closed_by_user_id']);
                $table->dropColumn('closed_by_user_id');
            }
            if (Schema::hasColumn('change_requests', 'final_remark')) {
                $table->dropColumn('final_remark');
            }
            if (Schema::hasColumn('change_requests', 'fop')) {
                $table->dropColumn('fop');
            }
            if (Schema::hasColumn('change_requests', 'paid_to_airline')) {
                $table->dropColumn('paid_to_airline');
            }
            if (Schema::hasColumn('change_requests', 'request_type')) {
                $table->dropColumn('request_type');
            }
        });
    }
};
