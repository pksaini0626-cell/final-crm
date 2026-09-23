<?php

namespace Tests\Feature;

use App\Mail\AuthFailedAgentNotificationMail;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthFailedNotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'manager']);
        Role::firstOrCreate(['name' => 'agent']);
    }

    public function test_admin_cancelling_auth_updates_status_to_failed_and_dispatches_email_to_agent(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'name' => 'Super Admin',
            'alias_name' => 'Admin Boss',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $agent = User::factory()->create([
            'name' => 'Agent John',
            'alias_name' => 'Agent John',
            'email' => 'agent.john@example.com',
            'role' => 'agent',
            'is_active' => true,
        ]);
        $agent->assignRole('agent');

        $booking = Booking::create([
            'booking_id' => 'BKFAIL01',
            'agent_id' => $agent->id,
            'booking_date' => now()->format('Y-m-d'),
            'call_type' => 'ppc',
            'vertical' => 'flights',
            'service_provided' => 'new_booking',
            'booking_portal' => 'internal',
            'currency' => 'USD',
            'total_amount' => 1450.00,
            'paid_to_airline' => 1200.00,
            'total_mco' => 250.00,
            'booking_status' => 'email_auth_sent',
            'payment_status' => 'pending',
            'card_holder_name' => 'Bob Smith',
            'email_address' => 'bob.smith@example.com',
            'calling_number' => '+15551234567',
            'airline_pnr' => 'AIR999',
            'airline_name' => 'United Airlines',
        ]);

        $booking->passengers()->create([
            'title' => 'Mr',
            'first_name' => 'Bob',
            'last_name' => 'Smith',
            'gender' => 'M',
        ]);

        // 1. Admin cancels authorization with a reason
        $reason = 'Customer card was declined by issuing bank (insufficient funds)';
        $response = $this->actingAs($admin)->post("/bookings/{$booking->id}/cancel-auth", [
            'reason' => $reason,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'booking_status' => 'failed',
            'payment_status' => 'cancelled',
        ]);

        // 2. Verify audit remark was recorded
        $this->assertDatabaseHas('booking_remarks', [
            'booking_id' => $booking->id,
            'user_id' => $admin->id,
            'type' => 'admin_remark',
        ]);

        // 3. Verify failure notification email was sent to agent
        Mail::assertSent(AuthFailedAgentNotificationMail::class, function ($mail) use ($agent, $booking, $reason) {
            return $mail->hasTo($agent->email) &&
                $mail->booking->id === $booking->id &&
                $mail->reason === $reason;
        });

        // 4. Verify the email renders correctly and contains required info
        $mailable = new AuthFailedAgentNotificationMail($booking->fresh(), $admin, $reason);
        $renderedHtml = $mailable->render();

        $this->assertStringContainsString('BKFAIL01', $renderedHtml);
        $this->assertStringContainsString('AIR999', $renderedHtml);
        $this->assertStringContainsString('Bob Smith', $renderedHtml);
        $this->assertStringContainsString('bob.smith@example.com', $renderedHtml);
        $this->assertStringContainsString('1,450.00', $renderedHtml);
        $this->assertStringContainsString('250.00', $renderedHtml);
        $this->assertStringContainsString('insufficient funds', $renderedHtml);
        $this->assertStringContainsString('NOT CHARGED', $renderedHtml);
        $this->assertStringContainsString('FAILED', $renderedHtml);
        $this->assertStringContainsString('contact with Admin team', $renderedHtml);

        // 5. Verify pending notifications list no longer includes this booking
        $adminDashboardResponse = $this->actingAs($admin)->get('/admin/bookings');
        $adminDashboardResponse->assertStatus(200);
        $adminDashboardResponse->assertDontSee('Pending Customer Authorization Notifications (1)');
    }

    private function createBooking(array $attributes = []): Booking
    {
        return Booking::create(array_merge([
            'booking_id' => 'BK' . rand(10000, 99999),
            'booking_date' => now()->format('Y-m-d'),
            'call_type' => 'ppc',
            'vertical' => 'flights',
            'service_provided' => 'new_booking',
            'booking_portal' => 'internal',
            'currency' => 'USD',
            'total_amount' => 500,
            'paid_to_airline' => 400,
            'total_mco' => 100,
            'booking_status' => 'booking_generated',
            'payment_status' => 'pending',
            'card_holder_name' => 'John Doe',
            'email_address' => 'johndoe@example.com',
        ], $attributes));
    }

    public function test_agent_cannot_cancel_auth(): void
    {
        $agent = User::factory()->create([
            'role' => 'agent',
            'alias_name' => 'Agent Bond',
        ]);
        $agent->assignRole('agent');

        $booking = $this->createBooking([
            'agent_id' => $agent->id,
            'booking_status' => 'email_auth_sent',
        ]);

        $response = $this->actingAs($agent)->post("/bookings/{$booking->id}/cancel-auth");
        $response->assertStatus(403);
    }

    public function test_admin_can_update_booking_status_to_failed_via_quick_update(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'alias_name' => 'Admin Boss',
        ]);
        $admin->assignRole('admin');

        $booking = $this->createBooking([
            'agent_id' => $admin->id,
            'booking_status' => 'booking_generated',
        ]);

        $response = $this->actingAs($admin)->post("/bookings/{$booking->id}/update-status", [
            'booking_status' => 'failed',
            'payment_status' => 'cancelled',
            'remark' => 'Charge failed during terminal processing',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'booking_status' => 'failed',
            'payment_status' => 'cancelled',
        ]);
    }
}
