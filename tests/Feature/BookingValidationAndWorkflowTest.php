<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Models\Merchant;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingValidationAndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'agent']);
        Role::create(['name' => 'manager']);

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
            'name' => 'John Agent',
            'alias_name' => 'John Agent',
            'email' => 'agent@example.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
            'is_active' => true,
        ]);
        $this->agent->assignRole('agent');
    }

    /**
     * Test booking creation fails if mandatory fields (passengers, PNR, card_last_4, email_address) are missing.
     */
    public function test_booking_creation_fails_without_mandatory_fields(): void
    {
        $response = $this->actingAs($this->agent)->postJson('/bookings', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'passengers',
            'gk_pnr',
            'airline_pnr',
            'card_last_4',
            'email_address',
        ]);
    }

    /**
     * Test booking creation sets status to booking_generated automatically.
     */
    public function test_booking_creation_defaults_to_booking_generated(): void
    {
        $response = $this->actingAs($this->agent)->post('/bookings', [
            'booking_date' => date('Y-m-d'),
            'call_type' => 'meta',
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'gds',
            'airline_pnr' => 'AA1234',
            'merchant' => 'Travelomile',
            'card_last_4' => '4321',
            'email_address' => 'customer@example.com',
            'currency' => 'USD',
            'total_amount' => 500.00,
            'paid_to_airline' => 400.00,
            'total_mco' => 100.00,
            'payment_status' => 'pending',
            'passengers' => [
                ['first_name' => 'John', 'last_name' => 'Doe', 'title' => 'MR', 'dob' => '1990-01-01', 'gender' => 'M']
            ]
        ]);

        $response->assertRedirect('/bookings');

        $this->assertDatabaseHas('bookings', [
            'airline_pnr' => 'AA1234',
            'booking_status' => 'booking_generated',
        ]);

        $this->assertDatabaseHas('passengers', [
            'first_name' => 'John',
            'gender' => 'M',
        ]);
    }

    public function test_booking_requires_mandatory_gender_field(): void
    {
        $response = $this->actingAs($this->agent)->post('/bookings', [
            'booking_date' => date('Y-m-d'),
            'call_type' => 'meta',
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'gds',
            'airline_pnr' => 'AA5678',
            'merchant' => 'Travelomile',
            'card_last_4' => '4321',
            'email_address' => 'customer2@example.com',
            'currency' => 'USD',
            'total_amount' => 500.00,
            'paid_to_airline' => 400.00,
            'total_mco' => 100.00,
            'payment_status' => 'pending',
            'passengers' => [
                ['first_name' => 'Jane', 'last_name' => 'Doe', 'title' => 'MS', 'dob' => '1992-02-02']
            ]
        ]);

        $response->assertSessionHasErrors(['passengers.0.gender']);
    }

    /**
     * Test full lifecycle workflow: booking_generated -> email_auth_sent -> email_auth_done -> ticketed.
     */
    public function test_full_booking_status_workflow(): void
    {
        Mail::fake();

        Merchant::create([
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

        // 1. Agent creates booking -> status = booking_generated
        $booking = Booking::create([
            'booking_date' => date('Y-m-d'),
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'gds',
            'airline_pnr' => 'FL9988',
            'card_last_4' => '9988',
            'email_address' => 'customer@example.com',
            'currency' => 'USD',
            'total_amount' => 300.00,
            'paid_to_airline' => 200.00,
            'total_mco' => 100.00,
            'payment_status' => 'pending',
            'booking_status' => 'booking_generated',
            'agent_id' => $this->agent->id,
        ]);

        $this->assertEquals('booking_generated', $booking->fresh()->booking_status);

        // 2. Agent dispatches auth email -> status = email_auth_sent
        $this->actingAs($this->agent)->post("/bookings/{$booking->id}/auth-email/send", [
            'email_address' => 'customer@example.com',
            'email_language' => 'english',
            'subject' => 'Authorization Mail',
        ]);

        $this->assertEquals('email_auth_sent', $booking->fresh()->booking_status);

        // 3. Admin approves customer authorization reply -> status = email_auth_done
        $response = $this->actingAs($this->admin)->post("/bookings/{$booking->id}/approve-auth");
        $response->assertSessionHas('success');

        $this->assertEquals('email_auth_done', $booking->fresh()->booking_status);
        $this->assertDatabaseHas('booking_remarks', [
            'booking_id' => $booking->id,
            'user_id' => $this->admin->id,
            'type' => 'admin_remark',
        ]);
    }
}
