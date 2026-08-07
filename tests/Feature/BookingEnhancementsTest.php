<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Merchant;
use App\Models\Passenger;
use App\Models\User;
use App\Services\MerchantMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_requires_mandatory_dob_call_type_and_merchant(): void
    {
        $agent = User::create([
            'name' => 'Agent Test',
            'alias_name' => 'Agent Test',
            'email' => 'agent.test@example.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
            'is_active' => true,
        ]);

        $this->actingAs($agent);

        // Missing DOB, invalid call_type, missing merchant
        $response = $this->post(route('bookings.store'), [
            'booking_date' => '2026-08-08',
            'call_type' => 'invalid_type',
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'airline_pnr' => 'TEST12',
            'currency' => 'USD',
            'total_amount' => 500.00,
            'paid_to_airline' => 400.00,
            'total_mco' => 100.00,
            'card_last_4' => '1234',
            'email_address' => 'customer@example.com',
            'payment_status' => 'pending',
            'passengers' => [
                [
                    'pax_index' => 'P1',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ]
            ]
        ]);

        $response->assertSessionHasErrors(['call_type', 'merchant', 'passengers.0.dob']);
    }

    public function test_booking_creation_succeeds_with_valid_dob_call_type_and_merchant(): void
    {
        $agent = User::create([
            'name' => 'Agent Test 2',
            'alias_name' => 'Agent Test 2',
            'email' => 'agent2.test@example.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
            'is_active' => true,
        ]);

        $merchant = Merchant::create([
            'merchant_code' => 'MCH001',
            'name' => 'Travelomile Merchant',
            'from_email' => 'reservations@travelomile.com',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_username' => 'smtp_user',
            'smtp_password' => 'smtp_pass',
            'smtp_encryption' => 'tls',
            'is_smtp_active' => true,
            'is_active' => true,
        ]);

        $this->actingAs($agent);

        $payload = [
            'booking_date' => '2026-08-08',
            'call_type' => 'meta',
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'airline_pnr' => 'VALID1',
            'merchant' => 'Travelomile Merchant',
            'currency' => 'USD',
            'total_amount' => 1000.00,
            'paid_to_airline' => 800.00,
            'total_mco' => 200.00,
            'card_last_4' => '4321',
            'email_address' => 'john.doe@example.com',
            'payment_status' => 'pending',
            'passengers' => [
                [
                    'pax_index' => 'P1',
                    'title' => 'MR',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'dob' => '1990-05-15',
                ]
            ]
        ];

        $response = $this->post(route('bookings.store'), $payload);
        $response->assertRedirect(route('bookings.index'));

        $this->assertDatabaseHas('bookings', [
            'airline_pnr' => 'VALID1',
            'call_type' => 'meta',
            'merchant' => 'Travelomile Merchant',
            'merchant_id' => $merchant->id,
        ]);

        $pax = Passenger::where('first_name', 'John')->where('last_name', 'Doe')->first();
        $this->assertNotNull($pax);
        $this->assertEquals('1990-05-15', $pax->dob->format('Y-m-d'));
    }

    public function test_auth_mail_uses_merchant_from_email_and_reservation_desk_name(): void
    {
        Mail::fake();

        $agent = User::create([
            'name' => 'Mail Agent',
            'alias_name' => 'Mail Agent',
            'email' => 'mailagent@example.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
            'is_active' => true,
        ]);

        $merchant = Merchant::create([
            'merchant_code' => 'MCH002',
            'name' => 'Desk Merchant',
            'from_email' => 'desk@traveldomain.com',
            'smtp_host' => 'smtp.mail.com',
            'smtp_port' => 587,
            'smtp_username' => 'user',
            'smtp_password' => 'pass',
            'is_smtp_active' => true,
            'is_active' => true,
        ]);

        $booking = Booking::create([
            'agent_id' => $agent->id,
            'merchant_id' => $merchant->id,
            'booking_id' => 'BK100200',
            'booking_date' => '2026-08-08',
            'call_type' => 'ppc',
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'airline_pnr' => 'DESKPNR',
            'merchant' => 'Desk Merchant',
            'currency' => 'USD',
            'total_amount' => 500.00,
            'paid_to_airline' => 400.00,
            'total_mco' => 100.00,
            'card_last_4' => '9999',
            'email_address' => 'client@example.com',
            'payment_status' => 'pending',
            'booking_status' => 'booking_generated',
        ]);

        Passenger::create([
            'booking_id' => $booking->id,
            'pax_index' => 'P1',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'dob' => '1995-10-20',
        ]);

        $service = new MerchantMailService();
        $service->sendAuthorizationEmail($booking);

        Mail::assertSent(\App\Mail\CustomerAuthMail::class, function ($mail) {
            return $mail->fromEmail === 'desk@traveldomain.com'
                && $mail->fromName === 'Reservation Desk';
        });
    }
}
