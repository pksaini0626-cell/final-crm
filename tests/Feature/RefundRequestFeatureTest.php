<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Merchant;
use App\Models\RefundRequest;
use App\Models\User;
use App\Mail\RefundRequestedNotificationMail;
use App\Mail\RefundApprovedAgentNotificationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RefundRequestFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $agentUser;
    protected User $misUser;
    protected Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'agent']);
        Role::firstOrCreate(['name' => 'mis']);
        Role::firstOrCreate(['name' => 'master_admin']);

        $this->adminUser = User::create([
            'name' => 'System Admin',
            'alias_name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->adminUser->assignRole('admin');

        $this->agentUser = User::create([
            'name' => 'Prashant Agent',
            'alias_name' => 'Prashant',
            'email' => 'agent@example.com',
            'password' => bcrypt('password123'),
            'role' => 'agent',
            'is_active' => true,
        ]);
        $this->agentUser->assignRole('agent');

        $this->misUser = User::create([
            'name' => 'MIS Team Member',
            'alias_name' => 'MISDesk',
            'email' => 'mis@example.com',
            'password' => bcrypt('password123'),
            'role' => 'mis',
            'is_active' => true,
        ]);
        $this->misUser->assignRole('mis');

        $this->merchant = Merchant::create([
            'name' => 'SkyPay Solutions',
            'merchant_code' => 'SKY01',
            'code' => 'SKY01',
            'is_active' => true,
        ]);
    }

    protected function createTicketedBooking(array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'booking_id' => Booking::generateUniqueBookingId(),
            'agent_id' => $this->agentUser->id,
            'merchant_id' => $this->merchant->id,
            'merchant' => $this->merchant->name,
            'booking_date' => now()->subDays(5)->format('Y-m-d'),
            'travel_date' => now()->addDays(10)->format('Y-m-d'),
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'airline_pnr' => 'AIR123',
            'airline_name' => 'Delta Airlines',
            'card_holder_name' => 'John Doe',
            'card_last_4' => '4321',
            'email_address' => 'john.doe@example.com',
            'billing_phone' => '1234567890',
            'currency' => 'USD',
            'total_amount' => 500.00,
            'paid_to_airline' => 300.00,
            'total_mco' => 200.00,
            'booking_status' => 'ticketed',
            'payment_status' => 'received',
            'vertical' => 'flight',
        ], $overrides));
    }

    public function test_agent_can_submit_refund_request_for_ticketed_booking(): void
    {
        $booking = $this->createTicketedBooking();

        $response = $this->actingAs($this->agentUser)->post(route('bookings.refund-request.store', $booking->id), [
            'request_type' => 'refund',
            'refund_amount' => 200.00,
            'refund_date' => now()->format('Y-m-d'),
            'reason_for_refund' => 'Customer called to cancel flight due to illness',
            'remarks' => 'Offered full refund as customer provided doctor prescription.',
        ]);

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('refund_requests', [
            'booking_id' => $booking->id,
            'agent_id' => $this->agentUser->id,
            'request_type' => 'refund',
            'refund_amount' => 200.00,
            'status' => 'pending',
        ]);

        $booking->refresh();
        $this->assertEquals('refund_pending', $booking->payment_status);

        // Verify remark was logged
        $this->assertDatabaseHas('booking_remarks', [
            'booking_id' => $booking->id,
            'user_id' => $this->agentUser->id,
            'type' => 'agent_remark',
        ]);
    }

    public function test_refund_request_fails_if_booking_is_not_ticketed(): void
    {
        $booking = $this->createTicketedBooking(['booking_status' => 'booking_generated']);

        $response = $this->actingAs($this->agentUser)->post(route('bookings.refund-request.store', $booking->id), [
            'request_type' => 'refund',
            'refund_amount' => 100.00,
            'refund_date' => now()->format('Y-m-d'),
            'reason_for_refund' => 'Test reason',
            'remarks' => 'Test remarks',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('refund_requests', ['booking_id' => $booking->id]);
    }

    public function test_refund_amount_cannot_exceed_total_mco(): void
    {
        $booking = $this->createTicketedBooking(['total_mco' => 150.00]);

        $response = $this->actingAs($this->agentUser)->post(route('bookings.refund-request.store', $booking->id), [
            'request_type' => 'refund',
            'refund_amount' => 250.00, // exceeds 150.00
            'refund_date' => now()->format('Y-m-d'),
            'reason_for_refund' => 'Customer cancelled',
            'remarks' => 'Wants full total amount',
        ]);

        $response->assertSessionHasErrors(['refund_amount']);
        $this->assertDatabaseMissing('refund_requests', ['booking_id' => $booking->id]);
    }

    public function test_remarks_field_is_mandatory(): void
    {
        $booking = $this->createTicketedBooking();

        $response = $this->actingAs($this->agentUser)->post(route('bookings.refund-request.store', $booking->id), [
            'request_type' => 'refund',
            'refund_amount' => 100.00,
            'refund_date' => now()->format('Y-m-d'),
            'reason_for_refund' => 'Schedule change',
            'remarks' => '', // empty remarks
        ]);

        $response->assertSessionHasErrors(['remarks']);
    }

    public function test_admin_can_approve_void_request_and_update_booking_payment_status(): void
    {
        $booking = $this->createTicketedBooking();

        $refundRequest = RefundRequest::create([
            'booking_id' => $booking->id,
            'agent_id' => $this->agentUser->id,
            'request_type' => 'void',
            'refund_amount' => 200.00,
            'currency' => 'USD',
            'refund_date' => now()->format('Y-m-d'),
            'reason_for_refund' => 'Customer void request within 24h',
            'remarks' => 'Voided immediately as per airline 24hr policy',
            'status' => 'pending',
        ]);
        $booking->update(['payment_status' => 'refund_pending']);

        $response = $this->actingAs($this->adminUser)->post(route('admin.refunds.approve', $refundRequest->id), [
            'mis_remarks' => 'Verified on gateway and void processed',
            'admin_remarks' => 'Approved by Admin',
            'deduction_from_agent' => 'nil',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $refundRequest->refresh();
        $this->assertEquals('approved', $refundRequest->status);
        $this->assertEquals($this->adminUser->id, $refundRequest->approved_by_id);
        $this->assertEquals('Verified on gateway and void processed', $refundRequest->mis_remarks);

        $booking->refresh();
        $this->assertEquals('void', $booking->payment_status);
        $this->assertEquals('void', $booking->case_status);
    }

    public function test_mis_team_can_reject_refund_request(): void
    {
        $booking = $this->createTicketedBooking();

        $refundRequest = RefundRequest::create([
            'booking_id' => $booking->id,
            'agent_id' => $this->agentUser->id,
            'request_type' => 'refund',
            'refund_amount' => 100.00,
            'currency' => 'USD',
            'refund_date' => now()->format('Y-m-d'),
            'reason_for_refund' => 'Customer change of mind',
            'remarks' => 'Requested partial refund',
            'status' => 'pending',
        ]);
        $booking->update(['payment_status' => 'refund_pending']);

        $response = $this->actingAs($this->misUser)->post(route('admin.refunds.reject', $refundRequest->id), [
            'rejection_reason' => 'Ticket is non-refundable as per airline rules',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $refundRequest->refresh();
        $this->assertEquals('rejected', $refundRequest->status);
        $this->assertEquals('Ticket is non-refundable as per airline rules', $refundRequest->admin_remarks);

        // Payment status should revert to received
        $booking->refresh();
        $this->assertEquals('received', $booking->payment_status);
    }

    public function test_mis_can_access_refund_report_sheet(): void
    {
        $booking = $this->createTicketedBooking();

        RefundRequest::create([
            'booking_id' => $booking->id,
            'agent_id' => $this->agentUser->id,
            'approved_by_id' => $this->adminUser->id,
            'request_type' => 'refund',
            'refund_amount' => 150.00,
            'currency' => 'USD',
            'refund_date' => now()->format('Y-m-d'),
            'reason_for_refund' => 'Flight cancelled',
            'remarks' => 'Partial refund provided',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        RefundRequest::create([
            'booking_id' => $booking->id,
            'agent_id' => $this->agentUser->id,
            'approved_by_id' => $this->adminUser->id,
            'request_type' => 'void',
            'refund_amount' => 80.00,
            'currency' => 'CAD',
            'refund_date' => now()->format('Y-m-d'),
            'reason_for_refund' => 'CAD cancellation',
            'remarks' => 'CAD void',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($this->misUser)->get(route('admin.reports.refunds'));
        $response->assertOk();
        $response->assertSee('Refund &amp; Void Request Report Sheet', false);
        $response->assertSee('AIR123');
        $response->assertSee('150.00');
        $response->assertSee('USD');
        $response->assertSee('CAD');
        $response->assertSee('80.00');
    }

    public function test_csv_export_returns_streamed_file_with_35_columns(): void
    {
        $booking = $this->createTicketedBooking();

        RefundRequest::create([
            'booking_id' => $booking->id,
            'agent_id' => $this->agentUser->id,
            'approved_by_id' => $this->adminUser->id,
            'request_type' => 'void',
            'refund_amount' => 200.00,
            'currency' => 'USD',
            'refund_date' => now()->format('Y-m-d'),
            'reason_for_refund' => 'Customer 24hr void',
            'remarks' => 'Full void processed',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.reports.refunds.export'));
        $response->assertOk();
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response->baseResponse);

        // Capture streamed content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // Verify CSV header columns exist
        $this->assertStringContainsString('Timestamp', $content);
        $this->assertStringContainsString('Date of Booking', $content);
        $this->assertStringContainsString('Agent Name', $content);
        $this->assertStringContainsString('Travel Date', $content);
        $this->assertStringContainsString('PNR', $content);
        $this->assertStringContainsString('Merchant Name', $content);
        $this->assertStringContainsString('Refund Amount', $content);
        $this->assertStringContainsString('Request Type', $content);
        $this->assertStringContainsString('Remarks', $content);
        $this->assertStringContainsString('MIS Remarks', $content);
        $this->assertStringContainsString('Refund age', $content);
        $this->assertStringContainsString('Status', $content);
        $this->assertStringContainsString('Updated on MCO Sheets', $content);
        $this->assertStringContainsString('Merchant as per MIS', $content);
        $this->assertStringContainsString('Dub/Uniqe', $content);

        // Verify data in CSV
        $this->assertStringContainsString('AIR123', $content);
        $this->assertStringContainsString('SkyPay Solutions (SKY01)', $content);
        $this->assertStringContainsString('200.00', $content);
    }

    public function test_submitting_refund_request_dispatches_email_to_mis_and_admins(): void
    {
        Mail::fake();

        $booking = $this->createTicketedBooking();

        $response = $this->actingAs($this->agentUser)->post(route('bookings.refund-request.store', $booking->id), [
            'request_type' => 'refund',
            'refund_amount' => 175.00,
            'refund_date' => now()->format('Y-m-d'),
            'reason_for_refund' => 'Medical reason',
            'remarks' => 'Passenger hospitalized, refund requested with docs',
        ]);

        $response->assertRedirect(route('bookings.index'));

        // Assert mail was dispatched with correct details
        Mail::assertSent(RefundRequestedNotificationMail::class, function ($mail) use ($booking) {
            $hasAdmin = $mail->hasTo($this->adminUser->email);
            $hasMis = $mail->hasTo($this->misUser->email);
            $correctBooking = $mail->refundRequest->booking_id === $booking->id;
            $correctAmount = (float)$mail->refundRequest->refund_amount === 175.00;
            $correctRemarks = $mail->refundRequest->remarks === 'Passenger hospitalized, refund requested with docs';

            return ($hasAdmin || $hasMis) && $correctBooking && $correctAmount && $correctRemarks;
        });
    }

    public function test_approving_refund_request_dispatches_email_directly_to_agent(): void
    {
        Mail::fake();

        $booking = $this->createTicketedBooking();

        $refundRequest = RefundRequest::create([
            'booking_id' => $booking->id,
            'agent_id' => $this->agentUser->id,
            'request_type' => 'refund',
            'refund_amount' => 150.00,
            'currency' => 'USD',
            'refund_date' => now()->format('Y-m-d'),
            'reason_for_refund' => 'Customer cancellation',
            'remarks' => 'Offered partial refund',
            'status' => 'pending',
        ]);
        $booking->update(['payment_status' => 'refund_pending']);

        $response = $this->actingAs($this->adminUser)->post(route('admin.refunds.approve', $refundRequest->id), [
            'admin_remarks' => 'Approved and refunded to card ending in 4321',
            'mis_remarks' => 'Processed on gateway terminal',
        ]);

        $response->assertRedirect();

        // Assert approval email was sent directly to the agent
        Mail::assertSent(RefundApprovedAgentNotificationMail::class, function ($mail) use ($refundRequest) {
            $sentToAgent = $mail->hasTo($this->agentUser->email);
            $correctBooking = $mail->refundRequest->booking_id === $refundRequest->booking_id;
            $correctAmount = (float)$mail->refundRequest->refund_amount === 150.00;
            $correctApprover = $mail->approver && $mail->approver->id === $this->adminUser->id;

            return $sentToAgent && $correctBooking && $correctAmount && $correctApprover;
        });
    }
}
