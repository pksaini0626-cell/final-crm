<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Passenger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingDashboardWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        // Create an agent user
        $this->agent = User::create([
            'name' => 'Agent Smith',
            'alias_name' => 'Smith A',
            'email' => 'smith@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    /**
     * Test booking creation page is accessible by authenticated users.
     */
    public function test_booking_create_page_requires_auth(): void
    {
        $this->get(route('bookings.create'))->assertRedirect('/login');

        $this->actingAs($this->agent)
            ->get(route('bookings.create'))
            ->assertStatus(200);
    }

    /**
     * Test store booking validations.
     */
    public function test_booking_store_validations(): void
    {
        $response = $this->actingAs($this->agent)
            ->postJson(route('bookings.store'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'booking_date',
                'call_type',
                'merchant',
                'vertical',
                'service_provided',
                'booking_portal',
                'gk_pnr',
                'airline_pnr',
                'card_last_4',
                'email_address',
                'passengers',
                'currency',
                'total_amount',
                'paid_to_airline',
                'total_mco',
                'payment_status',
            ]);
    }

    /**
     * Test successful booking creation with relationships.
     */
    public function test_successful_booking_store_with_relationships(): void
    {
        $payload = [
            'booking_date' => '2026-08-04',
            'call_type' => 'meta',
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'amadeus',
            'airline_pnr' => 'PNR123',
            'merchant' => 'Travelomile',
            'card_last_4' => '1234',
            'language' => 'English',
            'email_address' => 'customer@example.com',
            'billing_address' => '742 Evergreen Terrace, Springfield, OR 97477, USA',
            'currency' => 'USD',
            'total_amount' => 600.00,
            'paid_to_airline' => 450.00,
            'total_mco' => 150.00, // Explicitly passed
            'payment_status' => 'pending',
            'initial_remark' => 'Custom initial agent remark.',
            'passengers' => [
                [
                    'pax_index' => 'P1',
                    'title' => 'MR',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'dob' => '1990-01-01',
                    'gender' => 'M',
                    'ticket_number' => '1234567890',
                    'seat_number' => '12A'
                ]
            ],
            'flights' => [
                [
                    'flight_number' => 'AA100',
                    'airline_name' => 'American Airlines',
                    'origin_airport' => 'JFK',
                    'destination_airport' => 'LHR',
                    'departure_time' => '2026-08-10 10:00:00',
                    'arrival_time' => '2026-08-10 22:00:00',
                    'booking_class' => 'V'
                ]
            ]
        ];

        $response = $this->actingAs($this->agent)
            ->post(route('bookings.store'), $payload);

        $response->assertRedirect(route('bookings.index'));

        $this->assertDatabaseHas('bookings', [
            'airline_pnr' => 'PNR123',
            'agent_id' => $this->agent->id,
            'email_address' => 'customer@example.com',
            'billing_address' => '742 Evergreen Terrace, Springfield, OR 97477, USA',
            'total_mco' => 150.00,
            'booking_status' => 'booking_generated',
        ]);
        
        // Assert booking is saved in database and has auto-generated booking_id
        $booking = Booking::where('email_address', 'customer@example.com')->first();
        $this->assertNotNull($booking);
        $this->assertNotEmpty($booking->booking_id);
        $this->assertEquals(7, strlen($booking->booking_id));

        // Assert financial values are directly saved without subtraction logic overrides
        $this->assertEquals(150.00, $booking->total_mco);

        // Assert relationships are created
        $this->assertCount(1, $booking->passengers);
        $this->assertEquals('John', $booking->passengers->first()->first_name);

        $this->assertCount(1, $booking->bookingFlights);
        $this->assertEquals('AA100', $booking->bookingFlights->first()->flight_number);

        $this->assertCount(1, $booking->bookingRemarks);
        $this->assertEquals('Custom initial agent remark.', $booking->bookingRemarks->first()->remark);
    }

    /**
     * Test booking creation with multiple payment cards.
     */
    public function test_booking_creation_with_multiple_cards(): void
    {
        $payload = [
            'booking_date' => '2026-08-05',
            'call_type' => 'ppc',
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'airline_pnr' => 'MULTI1',
            'merchant' => 'Travelomile',
            'card_holder_name' => 'John Primary',
            'card_type' => 'Visa',
            'card_last_4' => '1111',
            'card_expiration' => '08/28',
            'email_address' => 'multicard@example.com',
            'currency' => 'USD',
            'total_amount' => 1000.00,
            'paid_to_airline' => 800.00,
            'total_mco' => 200.00,
            'payment_status' => 'pending',
            'passengers' => [
                [
                    'pax_index' => 'P1',
                    'title' => 'MR',
                    'first_name' => 'John',
                    'last_name' => 'Primary',
                    'dob' => '1990-01-01',
                    'gender' => 'M',
                    'ticket_number' => '999888777',
                    'seat_number' => '10A'
                ]
            ],
            'booking_cards' => [
                [
                    'card_holder_name' => 'Jane Secondary',
                    'card_type' => 'Mastercard',
                    'card_last_4' => '2222',
                    'card_expiration' => '11/29'
                ],
                [
                    'card_holder_name' => 'Corporate Amex',
                    'card_type' => 'American Express',
                    'card_last_4' => '3333',
                    'card_expiration' => '05/30'
                ]
            ]
        ];

        $response = $this->actingAs($this->agent)
            ->post(route('bookings.store'), $payload);

        $response->assertRedirect(route('bookings.index'));

        $booking = Booking::where('email_address', 'multicard@example.com')->first();
        $this->assertNotNull($booking);
        $this->assertEquals('Visa', $booking->card_type);
        $this->assertEquals('08/28', $booking->card_expiration);

        $this->assertCount(2, $booking->bookingCards);
        $this->assertEquals('Mastercard', $booking->bookingCards->first()->card_type);
        $this->assertEquals('2222', $booking->bookingCards->first()->card_last_4);
        $this->assertEquals('3333', $booking->bookingCards->last()->card_last_4);
    }

    /**
     * Test booking search and filter on dashboard.
     */
    public function test_booking_dashboard_search_and_filters(): void
    {
        // Create another agent to verify records separation
        $otherAgent = User::create([
            'name' => 'Agent Neo',
            'alias_name' => 'Neo',
            'email' => 'neo@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create bookings for both agents
        $booking1 = Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => '2026-08-04',
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'language' => 'English',
            'email_address' => 'john.doe@example.com',
            'booking_status' => 'booking_generated',
            'currency' => 'USD',
            'total_mco' => 100.00,
            'payment_status' => 'pending',
            'airline_pnr' => 'MATCHEDPNR',
        ]);

        Passenger::create([
            'booking_id' => $booking1->id,
            'first_name' => 'Alice',
            'last_name' => 'Smith',
        ]);

        $booking2 = Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => '2026-08-04',
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'language' => 'English',
            'email_address' => 'jane.doe@example.com',
            'booking_status' => 'ticketed',
            'currency' => 'USD',
            'total_mco' => 200.00,
            'payment_status' => 'received',
            'airline_pnr' => 'OTHERPNR',
        ]);

        // Booking belonging to another agent
        Booking::create([
            'agent_id' => $otherAgent->id,
            'booking_date' => '2026-08-04',
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'language' => 'English',
            'email_address' => 'matrix@example.com',
            'booking_status' => 'booking_generated',
            'currency' => 'USD',
            'total_mco' => 50.00,
            'payment_status' => 'pending',
        ]);

        // 1. Verify dashboard shows only this agent's bookings
        $response = $this->actingAs($this->agent)->get(route('bookings.index'));
        $response->assertStatus(200);
        $response->assertSee('john.doe@example.com');
        $response->assertSee('jane.doe@example.com');
        $response->assertDontSee('matrix@example.com');

        // 2. Search by passenger name
        $response = $this->actingAs($this->agent)->get(route('bookings.index', ['search' => 'Alice']));
        $response->assertSee('john.doe@example.com');
        $response->assertDontSee('jane.doe@example.com');

        // 3. Search by PNR
        $response = $this->actingAs($this->agent)->get(route('bookings.index', ['search' => 'MATCHEDPNR']));
        $response->assertSee('john.doe@example.com');
        $response->assertDontSee('jane.doe@example.com');

        // 4. Filter by status
        $response = $this->actingAs($this->agent)->get(route('bookings.index', ['status' => 'ticketed']));
        $response->assertSee('jane.doe@example.com');
        $response->assertDontSee('john.doe@example.com');
    }

    /**
     * Test appending a remark.
     */
    public function test_agent_can_append_remark(): void
    {
        $booking = Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => '2026-08-04',
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'language' => 'English',
            'booking_status' => 'booking_generated',
            'currency' => 'USD',
            'total_mco' => 100.00,
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($this->agent)
            ->post(route('bookings.add-remark', $booking), [
                'remark' => 'This is a new follow up note.',
            ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('booking_remarks', [
            'booking_id' => $booking->id,
            'user_id' => $this->agent->id,
            'remark' => 'This is a new follow up note.',
            'type' => 'agent_remark',
        ]);
    }

    /**
     * Test agent can upload PDF and image attachments along with remarks multiple times.
     */
    public function test_agent_can_append_remark_with_file_attachments(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $booking = Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => '2026-08-04',
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'language' => 'English',
            'booking_status' => 'booking_generated',
            'currency' => 'USD',
            'total_mco' => 100.00,
            'payment_status' => 'pending',
        ]);

        $pdfFile = \Illuminate\Http\UploadedFile::fake()->create('ticket_confirmation.pdf', 500, 'application/pdf');
        $imageFile = \Illuminate\Http\UploadedFile::fake()->create('passport_copy.png', 400, 'image/png');

        $response = $this->actingAs($this->agent)
            ->post(route('bookings.add-remark', $booking), [
                'remark' => 'Uploaded ticket confirmation and passport document.',
                'attachments' => [$pdfFile, $imageFile],
            ]);

        $response->assertRedirect();

        $remark = $booking->bookingRemarks()->latest()->first();
        $this->assertNotNull($remark);
        $this->assertEquals('Uploaded ticket confirmation and passport document.', $remark->remark);
        $this->assertCount(2, $remark->attachments_data);
        $this->assertEquals('pdf', $remark->attachments_data[0]['file_type']);
        $this->assertEquals('image', $remark->attachments_data[1]['file_type']);
    }

    /**
     * Test updating ticket and seat numbers.
     */
    public function test_agent_can_update_tickets_and_seats(): void
    {
        $booking = Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => '2026-08-04',
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'language' => 'English',
            'booking_status' => 'booking_generated',
            'currency' => 'USD',
            'total_mco' => 100.00,
            'payment_status' => 'pending',
        ]);

        $pax1 = Passenger::create([
            'booking_id' => $booking->id,
            'first_name' => 'Alice',
            'last_name' => 'Smith',
        ]);

        $pax2 = Passenger::create([
            'booking_id' => $booking->id,
            'first_name' => 'Bob',
            'last_name' => 'Smith',
        ]);

        $payload = [
            'passengers' => [
                [
                    'id' => $pax1->id,
                    'ticket_number' => 'T1111',
                    'seat_number' => '10A',
                ],
                [
                    'id' => $pax2->id,
                    'ticket_number' => 'T2222',
                    'seat_number' => '10B',
                ]
            ]
        ];

        $response = $this->actingAs($this->agent)
            ->post(route('bookings.update-tickets', $booking), $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('passengers', [
            'id' => $pax1->id,
            'ticket_number' => 'T1111',
            'seat_number' => '10A',
        ]);

        $this->assertDatabaseHas('passengers', [
            'id' => $pax2->id,
            'ticket_number' => 'T2222',
            'seat_number' => '10B',
        ]);
    }
}
