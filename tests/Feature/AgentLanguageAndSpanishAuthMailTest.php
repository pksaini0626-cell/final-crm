<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Models\Merchant;
use App\Mail\CustomerAuthMail;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AgentLanguageAndSpanishAuthMailTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agentSpanish;
    protected User $agentBoth;
    protected Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'agent']);
        Role::create(['name' => 'manager']);

        $this->admin = User::create([
            'name' => 'Admin User',
            'alias_name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->agentSpanish = User::create([
            'name' => 'Spanish Agent',
            'alias_name' => 'Spanish Agent',
            'email' => 'spanish_agent@example.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
            'agent_language' => 'spanish',
            'is_active' => true,
        ]);
        $this->agentSpanish->assignRole('agent');

        $this->agentBoth = User::create([
            'name' => 'Bilingual Agent',
            'alias_name' => 'Bilingual Agent',
            'email' => 'both_agent@example.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
            'agent_language' => 'both',
            'is_active' => true,
        ]);
        $this->agentBoth->assignRole('agent');

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
     * Test admin can create agent with language preference.
     */
    public function test_admin_can_assign_agent_language_preference(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'New Agent',
            'alias_name' => 'New Agent',
            'email' => 'newagent@example.com',
            'password' => 'password123',
            'role' => 'agent',
            'agent_language' => 'spanish',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', [
            'email' => 'newagent@example.com',
            'agent_language' => 'spanish',
        ]);
    }

    /**
     * Test booking creation works for all 13 services without language field.
     */
    public function test_booking_creation_supports_all_13_services(): void
    {
        $services = [
            'new_booking',
            'exchange',
            'cancellation',
            'refund',
            'seat_selection',
            'baggage_addition',
            'others',
            'cancel_and_refund',
            'name_correction',
            'flight_upgrade',
            'dob_correction',
            'pet_in_cabin',
            'ancillary_refund',
            'infant_ticket',
        ];

        foreach ($services as $service) {
            $response = $this->actingAs($this->agentSpanish)->post('/bookings', [
                'booking_date' => date('Y-m-d'),
                'call_type' => 'meta',
                'vertical' => 'flight',
                'service_provided' => $service,
                'booking_portal' => 'gds',
                'airline_pnr' => 'PNR123',
                'merchant' => 'Travelomile',
                'card_last_4' => '1234',
                'currency' => 'USD',
                'total_amount' => 150.00,
                'paid_to_airline' => 100.00,
                'total_mco' => 50.00,
                'payment_status' => 'pending',
                'email_address' => 'customer@example.com',
                'passengers' => [
                    ['first_name' => 'Maria', 'last_name' => 'Garcia', 'title' => 'MS', 'dob' => '1990-01-01', 'gender' => 'F']
                ]
            ]);

            $response->assertRedirect('/bookings');
            $this->assertDatabaseHas('bookings', [
                'service_provided' => $service,
            ]);
        }
    }

    /**
     * Test preview page defaults to Spanish for Spanish Agent.
     */
    public function test_preview_page_defaults_to_spanish_for_spanish_agent(): void
    {
        $booking = Booking::create([
            'booking_date' => date('Y-m-d'),
            'vertical' => 'flight',
            'service_provided' => 'pet_in_cabin',
            'booking_portal' => 'gds',
            'currency' => 'USD',
            'total_amount' => 200.00,
            'paid_to_airline' => 150.00,
            'total_mco' => 50.00,
            'payment_status' => 'pending',
            'booking_status' => 'booking_generated',
            'agent_id' => $this->agentSpanish->id,
            'email_address' => 'customer@example.com',
            'airline_name' => 'Iberia',
            'airline_pnr' => 'IB1234',
        ]);

        $response = $this->actingAs($this->agentSpanish)->get("/bookings/{$booking->id}/auth-email/preview");

        $response->assertStatus(200);
        $response->assertSee('Autorización para Iberia Mascota en Cabina Confirmación #IB1234');
        $response->assertSee('Autorización de Pago y Confirmación de Reserva');
    }

    /**
     * Test sending Spanish Auth Mail dispatches Spanish template.
     */
    public function test_sending_spanish_auth_mail(): void
    {
        Mail::fake();

        $booking = Booking::create([
            'booking_date' => date('Y-m-d'),
            'vertical' => 'flight',
            'service_provided' => 'name_correction',
            'booking_portal' => 'gds',
            'currency' => 'USD',
            'total_amount' => 100.00,
            'paid_to_airline' => 80.00,
            'total_mco' => 20.00,
            'payment_status' => 'pending',
            'booking_status' => 'booking_generated',
            'agent_id' => $this->agentBoth->id,
            'email_address' => 'customer@example.com',
            'airline_name' => 'American Airlines',
            'airline_pnr' => 'AA9988',
        ]);

        $response = $this->actingAs($this->agentBoth)->post("/bookings/{$booking->id}/auth-email/send", [
            'email_address' => 'customer@example.com',
            'email_language' => 'spanish',
            'subject' => 'Autorización para American Airlines Corrección de Nombre Confirmación #AA9988',
            'from_name' => 'Reservation Desk',
            'from_email' => 'reservation@travelomile.com',
            'agent_name' => 'Bilingual Agent',
            'agent_ext' => '187',
        ]);

        $response->assertRedirect('/bookings');

        Mail::assertSent(CustomerAuthMail::class, function ($mail) {
            return $mail->emailLanguage === 'spanish' && $mail->hasTo('customer@example.com');
        });
    }
}
