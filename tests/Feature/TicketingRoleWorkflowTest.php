<?php

namespace Tests\Feature;

use App\Mail\TicketingAssignmentMail;
use App\Models\Booking;
use App\Models\Merchant;
use App\Models\Passenger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TicketingRoleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $agentUser;
    protected User $ticketingUser;
    protected Merchant $merchant;
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RoleAndUserSeeder']);

        $this->adminUser = User::where('role', 'admin')->first();
        $this->agentUser = User::where('role', 'agent')->first();
        $this->ticketingUser = User::where('role', 'ticketing')->first();

        $this->merchant = Merchant::create([
            'merchant_code' => 'TESTMERCHANT',
            'name' => 'Test Merchant Agency',
            'smtp_host' => 'smtp.mailtrap.io',
            'smtp_port' => 2525,
            'smtp_username' => 'testuser',
            'smtp_password' => 'testpass',
            'from_address' => 'reservations@testmerchant.com',
            'from_name' => 'Test Reservations',
            'is_active' => true,
        ]);

        $this->booking = Booking::create([
            'booking_id' => 'TK12345',
            'agent_id' => $this->agentUser->id,
            'merchant_id' => $this->merchant->id,
            'booking_date' => now(),
            'call_type' => 'inbound',
            'vertical' => 'flights',
            'trip_type' => 'one_way',
            'service_provided' => 'flight_booking',
            'booking_portal' => 'internal_crm',
            'airline_pnr' => 'TESTPNR',
            'airline_name' => 'Air India',
            'airline_code' => 'AI',
            'from_airport' => 'DEL',
            'to_airport' => 'JFK',
            'from_city' => 'Delhi',
            'to_city' => 'New York',
            'travel_date' => now()->addDays(10),
            'language' => 'english',
            'card_holder_name' => 'John Smith',
            'calling_number' => '+15551234567',
            'billing_phone' => '+15551234567',
            'card_last_4' => '4321',
            'email_address' => 'john.smith@example.com',
            'booking_status' => 'email_auth_done',
            'case_status' => 'open',
            'currency' => 'USD',
            'total_mco' => 150.00,
        ]);

        Passenger::create([
            'booking_id' => $this->booking->id,
            'title' => 'Mr',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'ticket_number' => null,
            'seat_number' => null,
        ]);
    }

    /** @test */
    public function ticketing_user_can_login_and_access_ticketing_queue(): void
    {
        $response = $this->actingAs($this->ticketingUser)->get(route('manager.tickets.index'));
        $response->assertStatus(200);
        $response->assertSee('TK12345');
    }

    /** @test */
    public function agent_can_assign_booking_to_ticketing_user_and_trigger_email(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->agentUser)->post(route('bookings.assign-ticketing', $this->booking), [
            'ticketing_user_id' => $this->ticketingUser->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'ticketing_user_id' => $this->ticketingUser->id,
        ]);

        Mail::assertSent(TicketingAssignmentMail::class, function ($mail) {
            return $mail->hasTo($this->ticketingUser->email) &&
                   $mail->booking->id === $this->booking->id;
        });
    }

    /** @test */
    public function ticketing_agent_can_edit_ticket_numbers_and_preview_eticket_page(): void
    {
        // 1. View Preview E-Ticket Email page
        $response = $this->actingAs($this->ticketingUser)->get(route('manager.tickets.preview-email', $this->booking));
        $response->assertStatus(200);
        $response->assertSee('TK12345');

        // 2. Update Passenger ticket and seat numbers
        $passenger = $this->booking->passengers->first();
        $response = $this->actingAs($this->ticketingUser)->post(route('manager.tickets.update-ticket-details', $this->booking), [
            'trip_type' => 'round_trip',
            'passengers' => [
                $passenger->id => [
                    'ticket_number' => '098-2415908123',
                    'seat_number' => '14B',
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('passengers', [
            'id' => $passenger->id,
            'ticket_number' => '098-2415908123',
            'seat_number' => '14B',
        ]);
    }

    /** @test */
    public function ticketing_user_can_access_full_booking_edit_and_update_all_parameters(): void
    {
        // 1. Access edit page
        $editResponse = $this->actingAs($this->ticketingUser)->get(route('admin.bookings.edit', $this->booking));
        $editResponse->assertStatus(200);
        $editResponse->assertSee($this->booking->booking_id);

        // 2. Submit booking update
        $updatePayload = [
            'agent_id' => $this->agentUser->id,
            'booking_date' => now()->format('Y-m-d'),
            'call_type' => 'meta',
            'vertical' => 'flight',
            'trip_type' => 'round_trip',
            'service_provided' => 'ticketed_booking',
            'booking_portal' => 'internal_crm',
            'currency' => 'USD',
            'total_amount' => 600.00,
            'paid_to_airline' => 450.00,
            'total_mco' => 150.00,
            'booking_status' => 'ticketed',
            'payment_status' => 'received',
            'card_last_4' => '9999',
            'email_address' => 'updated.customer@example.com',
        ];

        $updateResponse = $this->actingAs($this->ticketingUser)->put(route('admin.bookings.update', $this->booking), $updatePayload);
        $updateResponse->assertRedirect(route('manager.tickets.index'));

        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'booking_status' => 'ticketed',
            'payment_status' => 'received',
            'email_address' => 'updated.customer@example.com',
        ]);
    }
}
