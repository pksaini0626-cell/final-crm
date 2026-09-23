<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ETicketTopTextWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'manager']);
        Role::firstOrCreate(['name' => 'ticketing']);
        Role::firstOrCreate(['name' => 'agent']);
    }

    private function createTestBooking(array $attributes = []): Booking
    {
        if (!isset($attributes['agent_id'])) {
            $defaultAgent = User::first() ?: User::factory()->create(['role' => 'agent', 'alias_name' => 'Default Agent']);
            $attributes['agent_id'] = $defaultAgent->id;
        }

        return Booking::create(array_merge([
            'booking_id' => 'BK' . rand(10000, 99999),
            'booking_date' => now()->format('Y-m-d'),
            'call_type' => 'ppc',
            'vertical' => 'flights',
            'service_provided' => 'new_booking',
            'booking_portal' => 'internal',
            'currency' => 'USD',
            'total_amount' => 600,
            'paid_to_airline' => 500,
            'total_mco' => 100,
            'booking_status' => 'ticketed',
            'payment_status' => 'received',
            'card_holder_name' => 'Sarah Connor',
            'email_address' => 'sarah@example.com',
            'airline_pnr' => 'PNR123',
            'airline_name' => 'Delta Airlines',
        ], $attributes));
    }

    public function test_manager_can_update_eticket_top_text_and_verify_position(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
            'alias_name' => 'Ticket Manager',
        ]);
        $manager->assignRole('manager');

        $booking = $this->createTestBooking([
            'agent_id' => $manager->id,
        ]);

        $customTopText = 'IMPORTANT NOTICE: Please check in online 24 hours prior to departure.';

        // 1. Submit update-top-text POST
        $response = $this->actingAs($manager)->post("/manager/tickets/{$booking->id}/update-top-text", [
            'eticket_top_text' => $customTopText,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'eticket_top_text' => $customTopText,
        ]);

        // 2. Audit remark created
        $this->assertDatabaseHas('booking_remarks', [
            'booking_id' => $booking->id,
            'user_id' => $manager->id,
            'type' => 'admin_remark',
        ]);

        // 3. Visit preview-email page and verify preview contains text and input
        $previewResponse = $this->actingAs($manager)->get("/manager/tickets/{$booking->id}/preview-email");
        $previewResponse->assertStatus(200);
        $previewResponse->assertSee($customTopText);
        $previewResponse->assertSee('name="eticket_top_text"', false);

        // 4. Verify rendered email HTML has top text positioned BEFORE Passenger / Ticket section
        $freshBooking = $booking->fresh();
        $emailHtml = view('emails.customer_e_ticket', [
            'booking' => $freshBooking,
            'topText' => $customTopText,
        ])->render();

        $this->assertStringContainsString($customTopText, $emailHtml);
        $this->assertStringContainsString('Passenger &amp; Ticket Roster', $emailHtml);

        $topTextPos = strpos($emailHtml, $customTopText);
        $passengerDetailsPos = strpos($emailHtml, 'Passenger &amp; Ticket Roster');

        $this->assertTrue($topTextPos !== false && $passengerDetailsPos !== false);
        $this->assertLessThan($passengerDetailsPos, $topTextPos, 'Top text must appear before Passenger & Ticket Details');

        // 5. Verify PDF view also includes top text before Passenger & Ticket Details
        $pdfHtml = view('pdf.e-ticket', [
            'booking' => $freshBooking,
        ])->render();

        $this->assertStringContainsString($customTopText, $pdfHtml);
        $pdfTopTextPos = strpos($pdfHtml, $customTopText);
        $pdfPassengerDetailsPos = strpos($pdfHtml, 'Passenger &amp; Ticket Details');

        $this->assertTrue($pdfTopTextPos !== false && $pdfPassengerDetailsPos !== false);
        $this->assertLessThan($pdfPassengerDetailsPos, $pdfTopTextPos, 'PDF Top text must appear before Passenger & Ticket Details');
    }

    public function test_unauthenticated_or_unauthorized_user_cannot_update_top_text(): void
    {
        $booking = $this->createTestBooking();

        // Guest cannot access
        $guestResponse = $this->post("/manager/tickets/{$booking->id}/update-top-text", [
            'eticket_top_text' => 'Hacked text',
        ]);
        $guestResponse->assertRedirect('/login');

        // Role other than manager|admin|ticketing|agent cannot access
        Role::firstOrCreate(['name' => 'customer']);
        $otherUser = User::factory()->create(['role' => 'customer', 'alias_name' => 'Customer']);
        $otherUser->assignRole('customer');

        $userResponse = $this->actingAs($otherUser)->post("/manager/tickets/{$booking->id}/update-top-text", [
            'eticket_top_text' => 'Disallowed text',
        ]);
        $userResponse->assertStatus(403);
    }
}
