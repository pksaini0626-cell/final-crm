<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateBookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $agentJohn;
    protected User $agentSandra;
    protected Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agentJohn = User::create([
            'name' => 'John Doe',
            'alias_name' => 'John D',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
            'is_active' => true,
        ]);

        $this->agentSandra = User::create([
            'name' => 'Sandra Bull',
            'alias_name' => 'Sandra B',
            'email' => 'sandra@example.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
            'is_active' => true,
        ]);

        $this->merchant = Merchant::create([
            'name' => 'Travelomile',
            'merchant_code' => 'travelomile_us',
            'wallet_balance' => 10000.00,
            'is_smtp_active' => true,
            'currency' => 'USD',
        ]);
    }

    /**
     * Test agent Sandra can search by Airline PNR and see booking created by agent John.
     */
    public function test_agent_can_search_pnr_belonging_to_another_agent(): void
    {
        // John creates a booking with PNR "XYZ999"
        $johnBooking = Booking::create([
            'agent_id' => $this->agentJohn->id,
            'merchant_id' => $this->merchant->id,
            'booking_date' => '2026-08-01',
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'airline_pnr' => 'XYZ999',
            'airline_name' => 'American Airlines',
            'email_address' => 'customer_john@example.com',
            'card_holder_name' => 'Customer John',
            'total_amount' => 500.00,
            'currency' => 'USD',
        ]);

        // Sandra searches without PNR -> should NOT see John's booking
        $response1 = $this->actingAs($this->agentSandra)->get(route('bookings.index'));
        $response1->assertStatus(200);
        $response1->assertDontSee('XYZ999');

        // Sandra searches for "XYZ999" -> SHOULD see John's booking
        $response2 = $this->actingAs($this->agentSandra)->get(route('bookings.index', ['search' => 'XYZ999']));
        $response2->assertStatus(200);
        $response2->assertSee('XYZ999');
        $response2->assertSee($johnBooking->booking_id);
    }

    /**
     * Test duplicate creation view loads source booking data for pre-filling.
     */
    public function test_duplicate_view_loads_source_booking(): void
    {
        $originalBooking = Booking::create([
            'agent_id' => $this->agentJohn->id,
            'merchant_id' => $this->merchant->id,
            'booking_date' => '2026-08-01',
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'airline_pnr' => 'PNR123',
            'airline_name' => 'Delta Air Lines',
            'email_address' => 'customer@example.com',
            'card_holder_name' => 'Alice Walker',
            'total_amount' => 800.00,
            'currency' => 'USD',
        ]);

        $response = $this->actingAs($this->agentSandra)->get(route('bookings.create', ['duplicate' => $originalBooking->id]));
        $response->assertStatus(200);
        $response->assertSee('Duplicating Booking #' . $originalBooking->booking_id);
        $response->assertSee('Alice Walker');
        $response->assertSee('PNR123');
    }

    /**
     * Test Sandra duplicates John's booking for a cancellation, resulting in a new booking owned by Sandra.
     */
    public function test_duplicate_booking_creates_new_record_for_current_agent(): void
    {
        $originalBooking = Booking::create([
            'agent_id' => $this->agentJohn->id,
            'merchant_id' => $this->merchant->id,
            'booking_date' => '2026-08-01',
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'airline_pnr' => 'CANCEL99',
            'airline_name' => 'United Airlines',
            'email_address' => 'customer_cancel@example.com',
            'card_holder_name' => 'Bob Builder',
            'card_type' => 'Visa',
            'card_last_4' => '4321',
            'card_expiration' => '12/28',
            'billing_address' => '123 Main St',
            'calling_number' => '+15551234567',
            'billing_phone' => '+15551234567',
            'total_amount' => 1000.00,
            'paid_to_airline' => 800.00,
            'total_mco' => 200.00,
            'currency' => 'USD',
        ]);

        $originalBooking->passengers()->create([
            'title' => 'MR',
            'first_name' => 'Bob',
            'last_name' => 'Builder',
            'pax_index' => 'P1',
        ]);

        // Sandra submits duplicated form for cancellation
        $payload = [
            'booking_date' => date('Y-m-d'),
            'call_type' => 'meta',
            'service_provided' => 'cancellation',
            'vertical' => 'flight',
            'trip_type' => 'one_way',
            'booking_portal' => 'website',
            'language' => 'English',
            'airline_pnr' => 'CANCEL99',
            'airline_name' => 'United Airlines',
            'email_address' => 'customer_cancel@example.com',
            'card_holder_name' => 'Bob Builder',
            'card_type' => 'Visa',
            'card_last_4' => '4321',
            'card_expiration' => '12/28',
            'billing_address' => '123 Main St',
            'calling_number' => '+15551234567',
            'billing_phone' => '+15551234567',
            'currency' => 'USD',
            'merchant' => $this->merchant->name,
            'total_amount' => 200.00,
            'paid_to_airline' => 50.00,
            'total_mco' => 150.00,
            'passengers' => [
                [
                    'pax_index' => 'P1',
                    'title' => 'MR',
                    'first_name' => 'Bob',
                    'last_name' => 'Builder',
                    'dob' => '1990-01-01',
                ]
            ],
            'payment_status' => 'pending',
            'initial_remark' => 'Duplicated from Booking #' . $originalBooking->booking_id . ' for cancellation processing.',
        ];

        $response = $this->actingAs($this->agentSandra)->postJson(route('bookings.store'), $payload);
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Check new booking exists, owned by Sandra, with service 'cancellation'
        $this->assertDatabaseHas('bookings', [
            'agent_id' => $this->agentSandra->id,
            'service_provided' => 'cancellation',
            'airline_pnr' => 'CANCEL99',
            'total_amount' => 200.00,
        ]);

        // Verify total bookings count is 2 (original + duplicated)
        $this->assertEquals(2, Booking::count());

        // Verify original booking is still owned by John with service 'new_booking'
        $originalBooking->refresh();
        $this->assertEquals($this->agentJohn->id, $originalBooking->agent_id);
        $this->assertEquals('new_booking', $originalBooking->service_provided);
    }
}
