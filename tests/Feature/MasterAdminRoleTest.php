<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MasterAdminRoleTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $masterAdminUser;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'master_admin']);
        Role::firstOrCreate(['name' => 'manager']);
        Role::firstOrCreate(['name' => 'agent']);

        $this->adminUser = User::create([
            'name' => 'Primary Admin',
            'alias_name' => 'Admin Alias',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->adminUser->assignRole('admin');

        $this->masterAdminUser = User::create([
            'name' => 'Master Administrator',
            'alias_name' => 'Master Alias',
            'email' => 'masteradmin@example.com',
            'password' => bcrypt('password'),
            'role' => 'master_admin',
            'is_active' => true,
        ]);
        $this->masterAdminUser->assignRole('master_admin');
    }

    public function test_master_admin_can_be_created_via_user_management(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.users.store'), [
            'name' => 'New Master Admin',
            'alias_name' => 'New Master Alias',
            'email' => 'newmaster@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'master_admin',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'newmaster@example.com',
            'role' => 'master_admin',
        ]);

        $newUser = User::where('email', 'newmaster@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->hasRole('master_admin'));
    }

    public function test_master_admin_can_be_updated_via_user_management(): void
    {
        $agent = User::create([
            'name' => 'Agent User',
            'alias_name' => 'Agent Alias',
            'email' => 'agent@example.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
            'is_active' => true,
        ]);
        $agent->assignRole('agent');

        $response = $this->actingAs($this->masterAdminUser)->put(route('admin.users.update', $agent), [
            'name' => 'Promoted User',
            'alias_name' => 'Promoted Alias',
            'email' => 'agent@example.com',
            'role' => 'master_admin',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $agent->id,
            'role' => 'master_admin',
        ]);

        $agent->refresh();
        $this->assertTrue($agent->hasRole('master_admin'));
    }

    public function test_master_admin_login_redirects_to_admin_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'masteradmin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->masterAdminUser);
    }

    public function test_master_admin_can_access_admin_panel_routes(): void
    {
        $routes = [
            'admin.dashboard',
            'admin.users.index',
            'admin.users.create',
            'admin.bookings.index',
            'admin.merchants.index',
            'admin.charges.index',
            'admin.reports.daily',
        ];

        foreach ($routes as $routeName) {
            $response = $this->actingAs($this->masterAdminUser)->get(route($routeName));
            $response->assertStatus(200);
        }
    }

    public function test_master_admin_sees_admin_panel_in_navigation(): void
    {
        $response = $this->actingAs($this->masterAdminUser)->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Admin Panel');
    }

    public function test_master_admin_can_access_booking_edit(): void
    {
        $booking = Booking::create([
            'booking_id' => 'BK-TEST-MA01',
            'agent_id' => $this->adminUser->id,
            'booking_date' => now(),
            'call_type' => 'inbound',
            'vertical' => 'flights',
            'trip_type' => 'one_way',
            'service_provided' => 'flight_booking',
            'booking_portal' => 'internal_crm',
            'airline_pnr' => 'TESTPNR',
            'airline_name' => 'Air India',
            'airline_code' => 'AI',
            'from_airport' => 'DEL',
            'to_airport' => 'JFK',
            'from_city' => 'Delhi',
            'to_city' => 'New York',
            'travel_date' => now()->addDays(10),
            'language' => 'english',
            'card_holder_name' => 'John Smith',
            'calling_number' => '+15551234567',
            'billing_phone' => '+15551234567',
            'card_last_4' => '4321',
            'email_address' => 'john.smith@example.com',
            'booking_status' => 'email_auth_done',
            'case_status' => 'open',
            'currency' => 'USD',
            'total_amount' => 100.00,
            'paid_to_airline' => 40.00,
            'company_card_used' => true,
            'company_card_amount' => 40.00,
            'total_mco' => 60.00,
        ]);

        $response = $this->actingAs($this->masterAdminUser)->get(route('admin.bookings.edit', $booking));
        $response->assertStatus(200);
    }
}
