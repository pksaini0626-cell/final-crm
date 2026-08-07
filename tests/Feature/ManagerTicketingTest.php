<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Merchant;
use App\Models\User;
use App\Mail\CustomerETicketMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ManagerTicketingTest extends TestCase
{
    use RefreshDatabase;

    protected User $agent;
    protected User $manager;
    protected Merchant $merchant;
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agent = User::create([
            'name' => 'Agent Smith',
            'alias_name' => 'Smith A',
            'email' => 'agent@example.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
        ]);

        $this->manager = User::create([
            'name' => 'Manager Jane',
            'alias_name' => 'Jane M',
            'email' => 'manager@example.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        $this->merchant = Merchant::create([
            'name' => 'SecurePay Ltd',
            'merchant_code' => 'securepay_us',
            'wallet_balance' => 5000.00,
            'smtp_host' => 'smtp.securepay.com',
            'smtp_port' => 587,
            'smtp_username' => 'api_user',
            'smtp_password' => 'secret_smtp_password',
            'smtp_encryption' => 'tls',
            'from_email' => 'no-reply@securepay.com',
            'from_name' => 'SecurePay Auto Mail',
            'is_smtp_active' => true,
            'currency' => 'USD',
        ]);

        $this->booking = Booking::create([
            'agent_id' => $this->agent->id,
            'merchant_id' => $this->merchant->id,
            'booking_date' => '2026-08-04',
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'language' => 'English',
            'email_address' => 'customer@example.com',
            'booking_status' => 'email_auth_done',
            'currency' => 'USD',
            'total_amount' => 850.00,
            'paid_to_airline' => 700.00,
            'total_mco' => 150.00,
            'payment_status' => 'pending',
            'card_holder_name' => 'David Miller',
            'card_last_4' => '9988',
        ]);
    }

    /**
     * Test role-based middleware protection for manager actions.
     */
    public function test_manager_ticketing_requires_auth_and_correct_role(): void
    {
        // 1. Guest redirected to login
        $this->get(route('manager.tickets.index'))->assertRedirect(route('login'));

        // 2. Agent receives 403 forbidden
        $this->actingAs($this->agent)
            ->get(route('manager.tickets.index'))
            ->assertStatus(403);

        // 3. Manager allowed in
        $this->actingAs($this->manager)
            ->get(route('manager.tickets.index'))
            ->assertStatus(200);
    }

    /**
     * Test payment status approval.
     */
    public function test_manager_payment_approval(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('manager.tickets.approve-payment', $this->booking), [
                'payment_status' => 'received',
            ]);

        $response->assertRedirect();
        
        $this->booking->refresh();
        $this->assertEquals('received', $this->booking->payment_status);

        // Verify manager remark is added
        $this->assertDatabaseHas('booking_remarks', [
            'booking_id' => $this->booking->id,
            'user_id' => $this->manager->id,
            'type' => 'admin_remark',
        ]);
    }

    /**
     * Test PDF e-ticket generation and streaming.
     */
    public function test_preview_eticket_generates_pdf_stream(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('manager.tickets.preview', $this->booking));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Test generating and dispatching E-Ticket email.
     */
    public function test_manager_can_generate_and_send_eticket(): void
    {
        Mail::fake();

        $this->assertNotNull($this->booking->merchant_id);
        $this->assertNotNull($this->booking->merchantProfile);

        $payload = [
            'booking_status' => 'booking_complete',
            'notes' => 'Priority ticketing completed.',
        ];

        $response = $this->actingAs($this->manager)
            ->post(route('manager.tickets.send', $this->booking), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        
        $this->booking->refresh();
        
        // Assert status updated
        $this->assertEquals('booking_complete', $this->booking->booking_status);

        // Assert mail was sent with attachment
        Mail::assertSent(CustomerETicketMail::class, function ($mail) {
            return $mail->hasTo('customer@example.com') &&
                   $mail->booking->id === $this->booking->id &&
                   ($mail->overrides['from_name'] ?? 'Reservation Desk') === 'Reservation Desk';
        });

        // Assert admin remark was saved
        $this->assertDatabaseHas('booking_remarks', [
            'booking_id' => $this->booking->id,
            'user_id' => $this->manager->id,
            'type' => 'admin_remark',
        ]);
    }

    /**
     * Test manager can view interactive e-ticket preview page.
     */
    public function test_manager_can_view_eticket_preview_page(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('manager.tickets.preview-email', $this->booking));

        $response->assertStatus(200);
        $response->assertViewIs('manager.tickets.eticket_preview');
        $response->assertViewHas('booking');
    }

    /**
     * Test updating passenger ticket numbers, seat numbers, and trip type.
     */
    public function test_manager_can_update_passenger_ticket_number_and_seat_number(): void
    {
        $passenger = $this->booking->passengers()->create([
            'first_name' => 'Michael',
            'last_name' => 'Jordan',
            'title' => 'MR',
        ]);

        $payload = [
            'trip_type' => 'round_trip',
            'passengers' => [
                $passenger->id => [
                    'ticket_number' => '006998877112',
                    'seat_number' => '12B',
                ],
            ],
        ];

        $response = $this->actingAs($this->manager)
            ->post(route('manager.tickets.update-ticket-details', $this->booking), $payload);

        $response->assertRedirect();

        $passenger->refresh();
        $this->assertEquals('006998877112', $passenger->ticket_number);
        $this->assertEquals('12B', $passenger->seat_number);

        $this->booking->refresh();
        $this->assertEquals('round_trip', $this->booking->trip_type);
    }
}
