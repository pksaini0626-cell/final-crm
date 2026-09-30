<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (class_exists(Role::class)) {
            Role::firstOrCreate(['name' => 'master_admin']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (class_exists(Role::class)) {
            $role = Role::where('name', 'master_admin')->first();
            if ($role) {
                $role->delete();
            }
        }
    }
};
