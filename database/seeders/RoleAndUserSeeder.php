<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class RoleAndUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Spatie roles exist
        $roles = ['admin', 'manager', 'agent', 'ticketing', 'changes', 'mis', 'chargeback'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // 2. Create or Update Admin Account
        $admin = User::updateOrCreate(
            ['email' => 'admin@callinggenie.com'],
            [
                'name' => 'System Admin',
                'alias_name' => 'Admin',
                'password' => Hash::make('Welcome@123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );
        $admin->assignRole('admin');

        // 3. Create or Update Agent Account
        $agent = User::updateOrCreate(
            ['email' => 'prashant.saini@trafficpirates.com'],
            [
                'name' => 'Prashant Saini',
                'alias_name' => 'Prashant Saini',
                'password' => Hash::make('Welcome@123'),
                'role' => 'agent',
                'is_active' => true,
            ]
        );
        $agent->assignRole('agent');

        // 4. Create or Update Ticketing Account
        $ticketingUser = User::updateOrCreate(
            ['email' => 'delhicsteam@callinggenie.com'],
            [
                'name' => 'Delhi CS Team',
                'alias_name' => 'Delhi Ticketing Desk',
                'password' => Hash::make('Welcome@123'),
                'role' => 'ticketing',
                'is_active' => true,
            ]
        );
        $ticketingUser->assignRole('ticketing');

        // 5. Create or Update Changes Account
        $changesUser = User::updateOrCreate(
            ['email' => 'changes@callinggenie.com'],
            [
                'name' => 'Changes Team',
                'alias_name' => 'Booking Changes Desk',
                'password' => Hash::make('Welcome@123'),
                'role' => 'changes',
                'is_active' => true,
            ]
        );
        $changesUser->assignRole('changes');

        // 6. Create or Update Merchant Charge Terminal User
        $chargeUser = User::updateOrCreate(
            ['email' => 'charge@callinggenie.com'],
            [
                'name' => 'Merchant Charge Desk',
                'alias_name' => 'Charge Terminal',
                'password' => Hash::make('Charge@123#'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );
        $chargeUser->assignRole('admin');

        // 7. Create or Update Chargeback Team User
        $chargebackUser = User::updateOrCreate(
            ['email' => 'chargeback@callinggenie.com'],
            [
                'name' => 'Chargeback Desk',
                'alias_name' => 'Chargeback Team',
                'password' => Hash::make('Welcome@123'),
                'role' => 'chargeback',
                'is_active' => true,
            ]
        );
        $chargebackUser->assignRole('chargeback');
    }
}
