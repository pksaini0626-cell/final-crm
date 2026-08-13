<?php

namespace Tests\Feature;

use App\Mail\AuthApprovedAgentNotificationMail;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthApprovedNotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'agent']);
    }

    public function test_admin_approving_auth_dispatches_email_to_agent_and_shows_dashboard_notification(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'alias_name' => 'Admin User',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $agent = User::factory()->create([
            'name' => 'Agent Smith',
            'alias_name' => 'Agent Smith',
            'email' => 'agent.smith@example.com',
            'role' => 'agent',
            'is_active' => true,
        ]);
        $agent->assignRole('agent');

        $booking = Booking::create([
            'booking_id' => 'BKAUTH77',
            'agent_id' => $agent->id,
            'booking_date' => now()->format('Y-m-d'),
            'call_type' => 'ppc',
            'vertical' => 'flights',
            'service_provided' => 'new_booking',
            'booking_portal' => 'internal',
            'currency' => 'USD',
            'total_amount' => 1250.00,
            'paid_to_airline' => 1000.00,
            'total_mco' => 250.00,
            'booking_status' => 'email_auth_sent',
            'payment_status' => 'pending',
            'card_holder_name' => 'Alice Johnson',
            'email_address' => 'alice@example.com',
            'card_last_4' => '9988',
        ]);

        // 1. Admin approves authorization
        $response = $this->actingAs($admin)->post("/bookings/{$booking->id}/approve-auth");

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'booking_status' => 'email_auth_done',
        ]);

        // 2. Verify email sent to agent's email address
        Mail::assertSent(AuthApprovedAgentNotificationMail::class, function ($mail) use ($agent, $booking) {
            return $mail->hasTo($agent->email) &&
                $mail->booking->id === $booking->id;
        });

        // 3. Verify Agent Dashboard shows Approved Customer Authorization notification banner
        $agentResponse = $this->actingAs($agent)->get('/bookings');

        $agentResponse->assertStatus(200);
        $agentResponse->assertSee('Approved Customer Authorizations', false);
        $agentResponse->assertSee('BKAUTH77');
        $agentResponse->assertSee('Alice Johnson');
    }
}
