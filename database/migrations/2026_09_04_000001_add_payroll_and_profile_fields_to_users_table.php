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
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_code', 50)->nullable()->after('alias_name')->index();
            $table->string('designation', 100)->nullable()->after('employee_code');
            $table->string('location', 100)->nullable()->after('designation');
            $table->date('leaving_date')->nullable()->after('joining_date');
            $table->string('tax_regime', 50)->nullable()->default('New Regime')->after('leaving_date'); // New Regime / Old Regime
            $table->string('pan', 20)->nullable()->after('tax_regime');
            $table->string('gender', 20)->nullable()->after('pan');
            $table->string('account_number', 60)->nullable()->after('gender');
            $table->string('pf_account_number', 60)->nullable()->after('account_number');
            $table->string('pf_uan', 60)->nullable()->after('pf_account_number');
            $table->string('esi_number', 60)->nullable()->after('pf_uan');
            $table->decimal('ctc', 14, 2)->nullable()->after('esi_number');
            $table->string('employment_type', 30)->default('Probation')->after('ctc'); // Probation / Permanent
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'employee_code',
                'designation',
                'location',
                'leaving_date',
                'tax_regime',
                'pan',
                'gender',
                'account_number',
                'pf_account_number',
                'pf_uan',
                'esi_number',
                'ctc',
                'employment_type',
            ]);
        });
    }
};
