<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBookingCreationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent1;
    protected User $agent2;
    protected Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'System Admin',
            'alias_name' => 'Admin Boss',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->agent1 = User::create([
            'name' => 'Agent One',
            'alias_name' => 'Agent One',
            'email' => 'agent1@example.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
            'is_active' => true,
        ]);

        $this->agent2 = User::create([
            'name' => 'Agent Two',
            'alias_name' => 'Agent Two',
            'email' => 'agent2@example.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
            'is_active' => true,
        ]);

        $this->merchant = Merchant::create([
            'name' => 'Global Pay',
            'merchant_code' => 'global_pay',
            'wallet_balance' => 10000.00,
            'currency' => 'USD',
            'is_active' => true,
        ]);
    }

    public function test_agent_creates_booking_and_auto_assigns_own_id(): void
    {
        $payload = [
            'booking_date' => '2026-08-06',
            'call_type' => 'meta',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'vertical' => 'flight',
            'airline_pnr' => 'AGENT1PNR',
            'card_last_4' => '1234',
            'email_address' => 'customer1@example.com',
            'currency' => 'USD',
            'merchant' => $this->merchant->name,
            'total_amount' => 500,
            'paid_to_airline' => 400,
            'total_mco' => 100,
            'payment_status' => 'pending',
            'passengers' => [
                ['first_name' => 'John', 'last_name' => 'Doe', 'dob' => '1990-01-01']
            ],
        ];

        $response = $this->actingAs($this->agent1)
            ->postJson(route('bookings.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('bookings', [
            'airline_pnr' => 'AGENT1PNR',
            'agent_id' => $this->agent1->id,
        ]);
    }

    public function test_admin_create_page_receives_active_agents_list(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('bookings.create'));

        $response->assertStatus(200);
        $response->assertViewHas('agents');
        
        $viewAgents = $response->viewData('agents');
        $this->assertTrue($viewAgents->contains($this->agent1));
        $this->assertTrue($viewAgents->contains($this->agent2));
    }

    public function test_admin_booking_creation_fails_without_selecting_agent(): void
    {
        $payload = [
            'booking_date' => '2026-08-06',
            'call_type' => 'meta',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'vertical' => 'flight',
            'airline_pnr' => 'ADMINPNR1',
            'card_last_4' => '5678',
            'email_address' => 'customer2@example.com',
            'currency' => 'USD',
            'merchant' => $this->merchant->name,
            'total_amount' => 800,
            'paid_to_airline' => 700,
            'total_mco' => 100,
            'payment_status' => 'pending',
            'passengers' => [
                ['first_name' => 'Alice', 'last_name' => 'Smith', 'dob' => '1990-01-01']
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('bookings.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['agent_id']);
    }

    public function test_admin_creates_booking_assigned_to_selected_agent(): void
    {
        $payload = [
            'agent_id' => $this->agent2->id,
            'booking_date' => '2026-08-06',
            'call_type' => 'meta',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'vertical' => 'flight',
            'airline_pnr' => 'ADMINPNR2',
            'card_last_4' => '9999',
            'email_address' => 'customer3@example.com',
            'currency' => 'USD',
            'merchant' => $this->merchant->name,
            'total_amount' => 1000,
            'paid_to_airline' => 900,
            'total_mco' => 100,
            'payment_status' => 'pending',
            'passengers' => [
                ['first_name' => 'Bob', 'last_name' => 'Marley', 'dob' => '1990-01-01']
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('bookings.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('bookings', [
            'airline_pnr' => 'ADMINPNR2',
            'agent_id' => $this->agent2->id,
        ]);

        $booking = Booking::where('airline_pnr', 'ADMINPNR2')->first();
        $this->assertEquals($this->agent2->id, $booking->agent_id);
        $this->assertNotEquals($this->admin->id, $booking->agent_id);
    }
}
