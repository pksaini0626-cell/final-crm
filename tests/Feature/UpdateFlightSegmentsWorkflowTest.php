<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdateFlightSegmentsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'agent']);
    }

    public function test_agent_can_update_itinerary_flight_segments(): void
    {
        $agent = User::factory()->create([
            'alias_name' => 'Agent Flight Tester',
            'role' => 'agent',
            'is_active' => true,
        ]);
        $agent->assignRole('agent');

        $booking = Booking::create([
            'booking_id' => 'BKFLIGHT01',
            'agent_id' => $agent->id,
            'booking_date' => now()->format('Y-m-d'),
            'call_type' => 'ppc',
            'vertical' => 'flights',
            'service_provided' => 'new_booking',
            'booking_portal' => 'internal',
            'currency' => 'USD',
            'total_amount' => 800.00,
            'paid_to_airline' => 600.00,
            'total_mco' => 200.00,
            'booking_status' => 'booking_generated',
            'payment_status' => 'pending',
            'card_holder_name' => 'Bob Miller',
            'email_address' => 'bob@example.com',
            'card_last_4' => '5544',
        ]);

        // Add initial segment
        $booking->flightSegments()->create([
            'segment_number' => 1,
            'operating_carrier' => 'DL',
            'flight_number' => '100',
            'origin_airport' => 'JFK',
            'destination_airport' => 'LAX',
            'departure_time' => '2026-09-01 08:00:00',
            'arrival_time' => '2026-09-01 11:30:00',
            'booking_class' => 'Y',
            'status' => 'Confirmed',
        ]);

        // Agent updates flight segments (adds 2 segments)
        $response = $this->actingAs($agent)->post("/bookings/{$booking->id}/update-tickets", [
            'airline_pnr' => 'NEWPNR',
            'flights' => [
                [
                    'operating_carrier' => 'AA',
                    'flight_number' => '450',
                    'origin_airport' => 'JFK',
                    'destination_airport' => 'MIA',
                    'departure_time' => '2026-09-10 10:00:00',
                    'arrival_time' => '2026-09-10 13:00:00',
                    'booking_class' => 'F',
                    'status' => 'Confirmed',
                ],
                [
                    'operating_carrier' => 'AA',
                    'flight_number' => '890',
                    'origin_airport' => 'MIA',
                    'destination_airport' => 'SFO',
                    'departure_time' => '2026-09-10 15:00:00',
                    'arrival_time' => '2026-09-10 18:30:00',
                    'booking_class' => 'F',
                    'status' => 'Confirmed',
                ],
            ]
        ]);

        $response->assertRedirect();
        
        $booking->refresh();

        $this->assertEquals('NEWPNR', $booking->airline_pnr);
        $this->assertCount(2, $booking->flightSegments);
        $this->assertCount(2, $booking->bookingFlights);

        $this->assertEquals('AA', $booking->flightSegments->first()->operating_carrier);
        $this->assertEquals('450', $booking->flightSegments->first()->flight_number);
        $this->assertEquals('JFK', $booking->flightSegments->first()->origin_airport);
        $this->assertEquals('MIA', $booking->flightSegments->first()->destination_airport);
    }
}
