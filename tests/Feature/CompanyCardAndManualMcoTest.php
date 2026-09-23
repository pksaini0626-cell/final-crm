<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyCardAndManualMcoTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent;
    protected Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'agent']);
        Role::create(['name' => 'manager']);
        Role::create(['name' => 'ticketing']);

        $this->admin = User::create([
            'name' => 'Admin User',
            'alias_name' => 'Admin Boss',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->agent = User::create([
            'name' => 'Steve Agent',
            'alias_name' => 'Steve Agent',
            'email' => 'steve@example.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
            'is_active' => true,
        ]);
        $this->agent->assignRole('agent');

        $this->merchant = Merchant::create([
            'merchant_code' => 'TRAVELOMILE',
            'name' => 'Travelomile',
            'gateway_type' => 'nmi',
            'is_active' => true,
        ]);
    }

    public function test_booking_creation_with_manual_mco_and_company_card(): void
    {
        // Steve charges $100 from customer, pays $40 to airline using company card, MCO manually entered as $60
        $bookingData = [
            'booking_date' => now()->toDateString(),
            'call_type' => 'meta',
            'vertical' => 'flight',
            'trip_type' => 'one_way',
            'service_provided' => 'exchange',
            'booking_portal' => 'website',
            'gk_pnr' => 'GK1234',
            'airline_pnr' => 'AL1234',
            'airline_code' => 'AA',
            'airline_name' => 'American Airlines',
            'from_airport' => 'JFK',
            'to_airport' => 'LAX',
            'card_holder_name' => 'Steve Customer',
            'card_type' => 'Visa',
            'card_last_4' => '4321',
            'email_address' => 'customer@example.com',
            'currency' => 'USD',
            'merchant' => $this->merchant->name,
            'total_amount' => 100.00,
            'paid_to_airline' => 40.00,
            'total_mco' => 60.00, // Manually entered
            'company_card_used' => true,
            'company_card_amount' => 40.00,
            'payment_status' => 'received',
            'passengers' => [
                [
                    'title' => 'MR',
                    'first_name' => 'Steve',
                    'last_name' => 'Customer',
                    'dob' => '1990-01-01',
                    'gender' => 'M',
                ],
            ],
        ];

        $response = $this->actingAs($this->agent)->post('/bookings', $bookingData);

        $response->assertRedirect(route('bookings.index'));

        $booking = Booking::where('airline_pnr', 'AL1234')->first();
        $this->assertNotNull($booking);
        $this->assertEquals(100.00, (float) $booking->total_amount);
        $this->assertEquals(40.00, (float) $booking->paid_to_airline);
        $this->assertEquals(60.00, (float) $booking->total_mco);
        $this->assertTrue($booking->company_card_used);
        $this->assertEquals(40.00, (float) $booking->company_card_amount);
    }

    public function test_booking_creation_allows_arbitrary_manual_mco(): void
    {
        // Total MCO doesn't have to strictly equal total_amount - paid_to_airline
        $bookingData = [
            'booking_date' => now()->toDateString(),
            'call_type' => 'ppc',
            'vertical' => 'flight',
            'trip_type' => 'round_trip',
            'service_provided' => 'new_booking',
            'booking_portal' => 'phone',
            'gk_pnr' => 'GK8888',
            'airline_pnr' => 'AL8888',
            'airline_code' => 'UA',
            'airline_name' => 'United Airlines',
            'from_airport' => 'ORD',
            'to_airport' => 'SFO',
            'card_holder_name' => 'Jane Doe',
            'card_type' => 'Mastercard',
            'card_last_4' => '8888',
            'email_address' => 'jane@example.com',
            'currency' => 'USD',
            'merchant' => $this->merchant->name,
            'total_amount' => 500.00,
            'paid_to_airline' => 350.00,
            'total_mco' => 125.00, // Custom manual MCO
            'company_card_used' => false,
            'payment_status' => 'pending',
            'passengers' => [
                [
                    'title' => 'MS',
                    'first_name' => 'Jane',
                    'last_name' => 'Doe',
                    'dob' => '1992-05-15',
                    'gender' => 'F',
                ],
            ],
        ];

        $response = $this->actingAs($this->agent)->post('/bookings', $bookingData);

        $response->assertRedirect(route('bookings.index'));

        $booking = Booking::where('airline_pnr', 'AL8888')->first();
        $this->assertNotNull($booking);
        $this->assertEquals(125.00, (float) $booking->total_mco);
        $this->assertFalse($booking->company_card_used);
        $this->assertEquals(0.00, (float) $booking->company_card_amount);
    }

    public function test_quick_company_card_endpoint_updates_booking(): void
    {
        $booking = Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => now()->toDateString(),
            'call_type' => 'meta',
            'vertical' => 'flight',
            'trip_type' => 'one_way',
            'service_provided' => 'exchange',
            'booking_portal' => 'website',
            'airline_pnr' => 'CC1234',
            'card_holder_name' => 'Customer Card',
            'card_last_4' => '1111',
            'email_address' => 'card@example.com',
            'currency' => 'USD',
            'merchant' => $this->merchant->name,
            'total_amount' => 200.00,
            'paid_to_airline' => 50.00,
            'total_mco' => 150.00,
            'payment_status' => 'received',
            'company_card_used' => false,
            'company_card_amount' => 0.00,
        ]);

        $response = $this->actingAs($this->agent)->postJson("/bookings/{$booking->id}/company-card", [
            'company_card_used' => 1,
            'company_card_amount' => 50.00,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'company_card_used' => true,
            'company_card_amount' => '50.00',
        ]);

        $booking->refresh();
        $this->assertTrue($booking->company_card_used);
        $this->assertEquals(50.00, (float) $booking->company_card_amount);
    }

    public function test_admin_edit_page_updates_company_card_details(): void
    {
        $booking = Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => now()->toDateString(),
            'call_type' => 'meta',
            'vertical' => 'flight',
            'trip_type' => 'one_way',
            'service_provided' => 'exchange',
            'booking_portal' => 'website',
            'airline_pnr' => 'EDIT12',
            'card_holder_name' => 'Edit Customer',
            'card_last_4' => '2222',
            'email_address' => 'edit@example.com',
            'currency' => 'USD',
            'merchant' => $this->merchant->name,
            'total_amount' => 300.00,
            'paid_to_airline' => 100.00,
            'total_mco' => 200.00,
            'booking_status' => 'ticketed',
            'payment_status' => 'received',
            'company_card_used' => false,
            'company_card_amount' => 0.00,
        ]);

        $updateData = [
            'agent_id' => $this->agent->id,
            'booking_date' => now()->toDateString(),
            'call_type' => 'meta',
            'vertical' => 'flight',
            'trip_type' => 'one_way',
            'service_provided' => 'exchange',
            'booking_portal' => 'website',
            'currency' => 'USD',
            'total_amount' => 300.00,
            'paid_to_airline' => 100.00,
            'total_mco' => 200.00,
            'company_card_used' => '1',
            'company_card_amount' => 100.00,
            'booking_status' => 'ticketed',
            'payment_status' => 'received',
            'email_address' => 'edit@example.com',
        ];

        $response = $this->actingAs($this->admin)->put("/admin/bookings/{$booking->id}", $updateData);

        $response->assertRedirect(route('admin.bookings.index'));

        $booking->refresh();
        $this->assertTrue($booking->company_card_used);
        $this->assertEquals(100.00, (float) $booking->company_card_amount);
    }

    public function test_slideover_update_saves_company_card_info(): void
    {
        $booking = Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => now()->toDateString(),
            'call_type' => 'meta',
            'vertical' => 'flight',
            'trip_type' => 'one_way',
            'service_provided' => 'exchange',
            'booking_portal' => 'website',
            'airline_pnr' => 'SLIDE1',
            'card_holder_name' => 'Slide Customer',
            'card_last_4' => '3333',
            'email_address' => 'slide@example.com',
            'currency' => 'USD',
            'merchant' => $this->merchant->name,
            'total_amount' => 150.00,
            'paid_to_airline' => 30.00,
            'total_mco' => 120.00,
            'payment_status' => 'received',
            'company_card_used' => false,
            'company_card_amount' => 0.00,
        ]);

        $response = $this->actingAs($this->agent)->post("/bookings/{$booking->id}/update-tickets", [
            'airline_pnr' => 'SLIDE1',
            'company_card_used' => '1',
            'company_card_amount' => 30.00,
        ]);

        $response->assertSessionHas('success');

        $booking->refresh();
        $this->assertTrue($booking->company_card_used);
        $this->assertEquals(30.00, (float) $booking->company_card_amount);
    }

    public function test_company_card_booking_shows_full_amount_on_merchant_in_auth_email(): void
    {
        // Steve charges $100 from customer, pays $40 to airline from company card
        $companyCardBooking = Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => now()->toDateString(),
            'vertical' => 'flight',
            'service_provided' => 'exchange',
            'booking_portal' => 'website',
            'language' => 'English',
            'currency' => 'USD',
            'total_amount' => 100.00,
            'paid_to_airline' => 40.00,
            'total_mco' => 60.00,
            'merchant' => $this->merchant->name,
            'airline_name' => 'American Airlines',
            'email_address' => 'steve@example.com',
            'card_holder_name' => 'Steve Customer',
            'card_last_4' => '9999',
            'company_card_used' => true,
            'company_card_amount' => 40.00,
        ]);

        $this->assertEquals(100.00, $companyCardBooking->merchant_charge_amount);

        // English auth email should show full 100.00 on Merchant and NOT split 40 to airline
        $enHtml = view('emails.customer_auth_email', ['booking' => $companyCardBooking])->render();
        $this->assertStringContainsString('Charges Description', $enHtml);
        $this->assertStringContainsString('USD 100.00', $enHtml);
        $this->assertStringContainsString('Travelomile, incl. the taxes and fees', $enHtml);
        $this->assertStringNotContainsString('Charge 1: <strong style="color: #0f172a;">USD 40.00</strong>', $enHtml);

        // Spanish auth email should also show full 100.00 on Merchant
        $esHtml = view('emails.customer_auth_email_es', ['booking' => $companyCardBooking])->render();
        $this->assertStringContainsString('USD 100.00', $esHtml);
        $this->assertStringContainsString('Travelomile, impuestos y cargos incluidos', $esHtml);
        $this->assertStringNotContainsString('Cargo 1: <strong style="color: #0f172a;">USD 40.00</strong>', $esHtml);
    }

    public function test_booking_creation_accepts_string_boolean_values_for_company_card(): void
    {
        $bookingData = [
            'booking_date' => now()->toDateString(),
            'call_type' => 'meta',
            'vertical' => 'flight',
            'trip_type' => 'one_way',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'language' => 'English',
            'airline_pnr' => 'STRBOOL1',
            'airline_code' => 'AA',
            'airline_name' => 'American Airlines',
            'currency' => 'USD',
            'merchant' => $this->merchant->name,
            'total_amount' => 150.00,
            'paid_to_airline' => 50.00,
            'total_mco' => 100.00,
            'company_card_used' => 'true', // string 'true' from FormData
            'company_card_amount' => 50.00,
            'card_holder_name' => 'String Test',
            'card_type' => 'Visa',
            'card_last_4' => '1234',
            'email_address' => 'str@example.com',
            'payment_status' => 'pending',
            'passengers' => [
                [
                    'pax_index' => 'P1',
                    'title' => 'MR',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'dob' => '1990-01-01',
                    'gender' => 'M',
                ],
            ],
        ];

        $response = $this->actingAs($this->agent)->postJson(route('bookings.store'), $bookingData);
        $response->assertStatus(200);
        $this->assertDatabaseHas('bookings', [
            'airline_pnr' => 'STRBOOL1',
            'company_card_used' => true,
            'company_card_amount' => 50.00,
        ]);

        $bookingData['airline_pnr'] = 'STRBOOL2';
        $bookingData['company_card_used'] = 'false';
        $bookingData['company_card_amount'] = 0.00;
        $response2 = $this->actingAs($this->agent)->postJson(route('bookings.store'), $bookingData);
        $response2->assertStatus(200);
        $this->assertDatabaseHas('bookings', [
            'airline_pnr' => 'STRBOOL2',
            'company_card_used' => false,
            'company_card_amount' => 0.00,
        ]);
    }
}
