<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Models\Merchant;
use App\Mail\CustomerAuthMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthEmailWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $agent;
    protected Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agent = User::create([
            'name' => 'Prashant Saini',
            'alias_name' => 'Prashant Saini',
            'email' => 'agent@example.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
            'is_active' => true,
        ]);

        $this->merchant = Merchant::create([
            'name' => 'Travelomile',
            'merchant_code' => 'TRAVELOMILE',
            'is_smtp_active' => true,
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587,
            'smtp_username' => 'test@example.com',
            'smtp_password' => 'secret',
            'smtp_encryption' => 'tls',
            'from_email' => 'reservation@travelomile.com',
            'from_name' => 'Reservation Desk',
        ]);
    }

    /**
     * Test booking creation does not automatically send email.
     */
    public function test_booking_creation_does_not_auto_send_email(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->agent)->post('/bookings', [
            'booking_date' => date('Y-m-d'),
            'call_type' => 'meta',
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'airline_pnr' => 'AA1234',
            'merchant' => 'Travelomile',
            'language' => 'English',
            'currency' => 'USD',
            'total_amount' => 500.00,
            'paid_to_airline' => 400.00,
            'total_mco' => 100.00,
            'payment_status' => 'pending',
            'email_address' => 'customer@example.com',
            'card_holder_name' => 'John Doe',
            'card_last_4' => '1234',
            'passengers' => [
                ['first_name' => 'John', 'last_name' => 'Doe', 'title' => 'MR', 'dob' => '1990-01-01', 'gender' => 'M']
            ]
        ]);

        $response->assertRedirect('/bookings');
        
        // Mail should NOT be sent automatically upon booking creation
        Mail::assertNothingSent();
    }

    /**
     * Test agent can view Auth Email preview page.
     */
    public function test_agent_can_view_auth_email_preview(): void
    {
        $booking = Booking::create([
            'booking_date' => date('Y-m-d'),
            'vertical' => 'flight',
            'service_provided' => 'baggage_addition',
            'booking_portal' => 'gds',
            'language' => 'English',
            'currency' => 'USD',
            'total_amount' => 500.00,
            'paid_to_airline' => 400.00,
            'total_mco' => 100.00,
            'payment_status' => 'pending',
            'booking_status' => 'booking_generated',
            'agent_id' => $this->agent->id,
            'email_address' => 'customer@example.com',
            'airline_name' => 'American Airlines',
            'airline_pnr' => 'AA4245',
        ]);

        $response = $this->actingAs($this->agent)->get("/bookings/{$booking->id}/auth-email/preview");

        $response->assertStatus(200);
        $response->assertSee('Generate Authorization Email');
        $response->assertSee('Authorization for American Airlines Baggage Edition Booking Confirmation #AA4245');
    }

    /**
     * Test agent can dispatch Auth Email to customer.
     */
    public function test_agent_can_send_auth_email_on_demand(): void
    {
        Mail::fake();

        $booking = Booking::create([
            'booking_date' => date('Y-m-d'),
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'gds',
            'currency' => 'USD',
            'total_amount' => 500.00,
            'paid_to_airline' => 400.00,
            'total_mco' => 100.00,
            'payment_status' => 'pending',
            'booking_status' => 'booking_generated',
            'agent_id' => $this->agent->id,
            'email_address' => 'customer@example.com',
        ]);

        $response = $this->actingAs($this->agent)->post("/bookings/{$booking->id}/auth-email/send", [
            'email_address' => 'customer@example.com',
            'email_language' => 'english',
            'subject' => 'Authorization for American Airline Baggage edition Booking Confirmation #AA4245',
            'from_name' => 'Reservation Desk',
            'from_email' => 'reservation@travelomile.com',
            'agent_name' => 'Prashant Saini',
            'agent_ext' => '187',
        ]);

        $response->assertRedirect('/bookings');

        Mail::assertSent(CustomerAuthMail::class, function ($mail) {
            return $mail->hasTo('customer@example.com');
        });

        Mail::assertSent(CustomerAuthMail::class, function ($mail) {
            return $mail->hasTo('agent@example.com');
        });

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'booking_status' => 'email_auth_sent',
        ]);
    }

    /**
     * Test authorization email is sent separately to both customer and agent without CC/BCC.
     */
    public function test_auth_email_sent_separately_to_agent_without_cc_or_bcc(): void
    {
        Mail::fake();

        $booking = Booking::create([
            'booking_date' => date('Y-m-d'),
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'gds',
            'currency' => 'USD',
            'total_amount' => 500.00,
            'paid_to_airline' => 400.00,
            'total_mco' => 100.00,
            'payment_status' => 'pending',
            'booking_status' => 'booking_generated',
            'agent_id' => $this->agent->id,
            'email_address' => 'customer@example.com',
        ]);

        $service = new \App\Services\MerchantMailService();
        $service->sendAuthorizationEmail($booking, [
            'email_address' => 'customer@example.com',
            'subject' => 'Authorization Mail Test',
        ]);

        // Verify two separate CustomerAuthMail mailables dispatched
        Mail::assertSent(CustomerAuthMail::class, 2);

        // Verify one is addressed to customer, one addressed to agent
        Mail::assertSent(CustomerAuthMail::class, function ($mail) {
            return $mail->hasTo('customer@example.com') && empty($mail->cc) && empty($mail->bcc);
        });

        Mail::assertSent(CustomerAuthMail::class, function ($mail) {
            return $mail->hasTo('agent@example.com') && empty($mail->cc) && empty($mail->bcc);
        });
    }

    /**
     * Test charges description renders correctly for single vs split airline payments.
     */
    public function test_charges_description_renders_single_or_split_airline_payment(): void
    {
        // 1. Single payment case (paid_to_airline = 0)
        $singleBooking = Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => date('Y-m-d'),
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'language' => 'English',
            'currency' => 'USD',
            'total_amount' => 150.00,
            'paid_to_airline' => 0.00,
            'total_mco' => 150.00,
            'merchant' => 'Travelomile',
            'airline_name' => 'Emirates Airline',
            'email_address' => 'customer1@example.com',
            'card_holder_name' => 'Jane Doe',
            'card_last_4' => '9876',
        ]);

        $singleHtml = view('emails.customer_auth_email', ['booking' => $singleBooking])->render();
        $this->assertStringContainsString('Charges Description', $singleHtml);
        $this->assertStringContainsString('1. <strong style="color: #0f172a;">USD 150.00</strong> (Travelomile, incl. the taxes and fees)', $singleHtml);

        // 2. Split airline payment case (paid_to_airline > 0)
        $splitBooking = Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => date('Y-m-d'),
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'language' => 'English',
            'currency' => 'USD',
            'total_amount' => 3961.90,
            'paid_to_airline' => 3661.90,
            'total_mco' => 300.00,
            'merchant' => 'TraveloMile.com',
            'airline_name' => 'Emirates Airline',
            'email_address' => 'customer2@example.com',
            'card_holder_name' => 'John Smith',
            'card_last_4' => '5432',
        ]);

        $splitHtml = view('emails.customer_auth_email', ['booking' => $splitBooking])->render();
        $this->assertStringContainsString('Charges Description:', $splitHtml);
        $this->assertStringContainsString('Charge 1: <strong style="color: #0f172a;">USD 3,661.90</strong> (Emirates Airline, incl. base fare)', $splitHtml);
        $this->assertStringContainsString('Charge 2: <strong style="color: #0f172a;">USD 300.00</strong> (TraveloMile.com, incl. taxes &amp; fees)', $splitHtml);
    }
}
