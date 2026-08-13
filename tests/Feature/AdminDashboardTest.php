<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'agent']);
    }

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_agent_cannot_access_admin_dashboard(): void
    {
        $agent = User::factory()->create([
            'alias_name' => 'Test Agent',
            'role' => 'agent',
            'is_active' => true,
        ]);
        $agent->assignRole('agent');

        $response = $this->actingAs($agent)->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_dashboard_and_see_metrics(): void
    {
        $admin = User::factory()->create([
            'alias_name' => 'Test Admin',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $agent = User::factory()->create([
            'role' => 'agent',
            'alias_name' => 'Top Agent 1',
            'is_active' => true,
            'last_login_at' => now(),
        ]);
        $agent->assignRole('agent');

        $booking = Booking::create([
            'booking_id' => 'BKDASH01',
            'agent_id' => $agent->id,
            'booking_date' => now()->format('Y-m-d'),
            'call_type' => 'ppc',
            'vertical' => 'flights',
            'service_provided' => 'new_booking',
            'booking_portal' => 'internal',
            'currency' => 'USD',
            'total_amount' => 1500.00,
            'paid_to_airline' => 1200.00,
            'total_mco' => 300.00,
            'booking_status' => 'booking_generated',
            'payment_status' => 'received',
            'card_holder_name' => 'John Doe',
            'email_address' => 'john@example.com',
            'card_last_4' => '1234',
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
        $response->assertSee('Executive Admin Dashboard');
        $response->assertSee("Today's Performance", false);
        $response->assertSee('Top Agent 1');
        $response->assertSee('300.00');
    }
}
