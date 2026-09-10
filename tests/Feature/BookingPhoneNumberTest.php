<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPhoneNumberTest extends TestCase
{
    use RefreshDatabase;

    protected User $agent;
    protected Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agent = User::create([
            'name' => 'John Agent',
            'alias_name' => 'Agent John',
            'email' => 'agent@callinggenie.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
            'is_active' => true,
        ]);

        $this->merchant = Merchant::create([
            'name' => 'Travelomile',
            'merchant_code' => 'travelomile',
            'wallet_balance' => 5000.00,
            'currency' => 'USD',
            'is_active' => true,
        ]);
    }

    public function test_booking_creation_persists_calling_number_and_billing_phone(): void
    {
        $payload = [
            'booking_date' => '2026-08-06',
            'call_type' => 'meta',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'vertical' => 'flight',
            'airline_pnr' => 'TESTPH1',
            'airline_code' => 'AA',
            'airline_name' => 'American Airlines',
            'calling_number' => '+18885551234',
            'billing_phone' => '+18885554321',
            'card_holder_name' => 'Jane Doe',
            'card_last_4' => '9999',
            'email_address' => 'janedoe@example.com',
            'currency' => 'USD',
            'merchant' => 'Travelomile',
            'total_amount' => 500.00,
            'paid_to_airline' => 450.00,
            'total_mco' => 50.00,
            'payment_status' => 'pending',
            'passengers' => [
                [
                    'first_name' => 'Jane',
                    'last_name' => 'Doe',
                    'title' => 'MS',
                    'dob' => '1990-01-01',
                    'gender' => 'F',
                ]
            ],
            'flights' => [
                [
                    'flight_number' => 'AA101',
                    'origin_airport' => 'JFK',
                    'destination_airport' => 'LAX',
                ]
            ],
        ];

        $response = $this->actingAs($this->agent)->postJson('/bookings', $payload);
        $response->assertStatus(200);

        $booking = Booking::where('airline_pnr', 'TESTPH1')->first();
        $this->assertNotNull($booking);
        $this->assertEquals('+18885551234', $booking->calling_number);
        $this->assertEquals('+18885554321', $booking->billing_phone);

        // Verify preview shows phone and NOT 'N/A'
        $previewResponse = $this->actingAs($this->agent)->get(route('bookings.auth-email.preview', $booking));
        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('+18885551234');
    }

    public function test_update_tickets_and_seats_updates_phone_numbers(): void
    {
        $booking = Booking::create([
            'booking_id' => 'BK99001',
            'booking_date' => '2026-08-06',
            'call_type' => 'meta',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'vertical' => 'flight',
            'airline_pnr' => 'UPDAT1',
            'agent_id' => $this->agent->id,
            'card_holder_name' => 'Test User',
            'email_address' => 'test@example.com',
            'currency' => 'USD',
            'merchant' => 'Travelomile',
            'total_amount' => 300.00,
            'paid_to_airline' => 250.00,
            'total_mco' => 50.00,
            'payment_status' => 'pending',
            'calling_number' => null,
            'billing_phone' => null,
        ]);

        $passenger = $booking->passengers()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'title' => 'MR',
        ]);

        $response = $this->actingAs($this->agent)->post(route('bookings.update-tickets', $booking), [
            'calling_number' => '+19998887777',
            'billing_phone' => '+19998886666',
            'passengers' => [
                [
                    'id' => $passenger->id,
                    'ticket_number' => '0011223344',
                    'seat_number' => '12A',
                ]
            ],
        ]);

        $response->assertStatus(302);
        $booking->refresh();
        $this->assertEquals('+19998887777', $booking->calling_number);
        $this->assertEquals('+19998886666', $booking->billing_phone);
    }

    public function test_send_auth_email_updates_missing_phone(): void
    {
        $booking = Booking::create([
            'booking_id' => 'BK99002',
            'booking_date' => '2026-08-06',
            'call_type' => 'meta',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'vertical' => 'flight',
            'airline_pnr' => 'PHONE2',
            'agent_id' => $this->agent->id,
            'card_holder_name' => 'Customer Two',
            'email_address' => 'cust2@example.com',
            'currency' => 'USD',
            'merchant' => 'Travelomile',
            'total_amount' => 400.00,
            'paid_to_airline' => 350.00,
            'total_mco' => 50.00,
            'payment_status' => 'pending',
            'calling_number' => null,
            'billing_phone' => null,
        ]);

        $response = $this->actingAs($this->agent)->post(route('bookings.auth-email.send', $booking), [
            'email_address' => 'cust2@example.com',
            'subject' => 'Authorization for Flight Booking',
            'customer_phone' => '+17778889999',
            'email_language' => 'english',
        ]);

        $response->assertStatus(302);
        $booking->refresh();
        $this->assertEquals('+17778889999', $booking->calling_number);
        $this->assertEquals('+17778889999', $booking->billing_phone);
    }
}
