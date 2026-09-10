<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class HrAndAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Roles exist
        $hrRole = Role::firstOrCreate(['name' => 'hr']);
        $accountsRole = Role::firstOrCreate(['name' => 'accounts']);

        // 2. Set passwords
        $hrPassword = 'CallingGenie@HR2026';
        $accountsPassword = 'CallingGenie@HR2026';

        // 3. Seed HR User
        $hrUser = User::updateOrCreate(
            ['email' => 'hr@callinggenie.com'],
            [
                'name' => 'HR Operations',
                'alias_name' => 'HR Desk',
                'password' => Hash::make($hrPassword),
                'role' => 'hr',
                'contact' => '+18005550190',
                'extension' => '1001',
                'designation' => 'HR Manager',
                'location' => 'Headquarters',
                'joining_date' => now()->subMonths(6)->toDateString(),
                'employment_type' => 'Permanent',
                'is_active' => true,
            ]
        );
        $hrUser->syncRoles(['hr']);

        // 4. Seed Accounts User
        $accountsUser = User::updateOrCreate(
            ['email' => 'accounts@callinggenie.com'],
            [
                'name' => 'Accounts & Payroll',
                'alias_name' => 'Accounts Desk',
                'password' => Hash::make($accountsPassword),
                'role' => 'accounts',
                'contact' => '+18005550191',
                'extension' => '1002',
                'designation' => 'Payroll Specialist',
                'location' => 'Headquarters',
                'joining_date' => now()->subMonths(6)->toDateString(),
                'employment_type' => 'Permanent',
                'is_active' => true,
            ]
        );
        $accountsUser->syncRoles(['accounts']);

        // Save generated passwords to a secure file in storage/app/seeded_credentials.json so it can be retrieved if needed
        $credentials = [
            'hr' => [
                'email' => 'hr@callinggenie.com',
                'password' => $hrPassword,
                'role' => 'hr',
            ],
            'accounts' => [
                'email' => 'accounts@callinggenie.com',
                'password' => $accountsPassword,
                'role' => 'accounts',
            ],
            'generated_at' => now()->toIso8601String(),
        ];

        file_put_contents(storage_path('app/seeded_credentials.json'), json_encode($credentials, JSON_PRETTY_PRINT));

        $this->command->info("HR User created: hr@callinggenie.com / {$hrPassword}");
        $this->command->info("Accounts User created: accounts@callinggenie.com / {$accountsPassword}");
    }
}
