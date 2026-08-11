<?php

namespace Tests\Feature;

use App\Mail\ChangeRequestCreatedMail;
use App\Mail\ChangeRequestStatusUpdatedMail;
use App\Models\Booking;
use App\Models\ChangeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ChangeRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $agent;
    protected User $changesUser;
    protected User $admin;
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agent = User::factory()->create([
            'alias_name' => 'Agent Smith',
            'email' => 'agent@example.com',
            'role' => 'agent',
            'is_active' => true,
        ]);

        $this->changesUser = User::factory()->create([
            'alias_name' => 'Changes Desk',
            'email' => 'changes@callinggenie.com',
            'role' => 'changes',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'alias_name' => 'Admin User',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->booking = Booking::create([
            'booking_id' => 'CHG1234',
            'agent_id' => $this->agent->id,
            'booking_date' => '2026-08-07',
            'vertical' => 'flight',
            'trip_type' => 'one_way',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'language' => 'English',
            'card_last_4' => '4321',
            'email_address' => 'customer@example.com',
            'currency' => 'USD',
            'total_amount' => 500.00,
            'paid_to_airline' => 400.00,
            'total_mco' => 100.00,
            'payment_status' => 'pending',
            'booking_status' => 'email_auth_done',
        ]);
    }

    public function test_agent_can_view_change_request_page(): void
    {
        $response = $this->actingAs($this->agent)
            ->get(route('bookings.request-change.create', $this->booking->id));

        $response->assertOk();
        $response->assertSee('Submit Change Request');
        $response->assertSee('#CHG1234');
    }

    public function test_agent_can_submit_change_request_and_trigger_email(): void
    {
        Mail::fake();

        $payload = [
            'change_request_text' => 'Please change flight departure date from Aug 10 to Aug 15.',
            'agent_remark' => 'Customer paid change fee.',
        ];

        $response = $this->actingAs($this->agent)
            ->post(route('bookings.request-change', $this->booking->id), $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('change_requests', [
            'booking_id' => $this->booking->id,
            'agent_id' => $this->agent->id,
            'change_request_text' => 'Please change flight departure date from Aug 10 to Aug 15.',
            'status' => 'pending',
        ]);

        Mail::assertSent(ChangeRequestCreatedMail::class, function ($mail) {
            return $mail->hasTo('changes@callinggenie.com') &&
                   $mail->changeRequest->booking_id === $this->booking->id;
        });
    }

    public function test_changes_user_can_access_changes_queue(): void
    {
        ChangeRequest::create([
            'booking_id' => $this->booking->id,
            'agent_id' => $this->agent->id,
            'change_request_text' => 'Seat change request to 12A.',
            'status' => 'pending',
            'assigned_at' => now(),
        ]);

        $response = $this->actingAs($this->changesUser)
            ->get(route('changes.index'));

        $response->assertOk();
        $response->assertSee('Seat change request to 12A.');
        $response->assertSee('#CHG1234');
    }

    public function test_changes_user_can_update_status_to_working_and_notify_agent(): void
    {
        Mail::fake();

        $changeRequest = ChangeRequest::create([
            'booking_id' => $this->booking->id,
            'agent_id' => $this->agent->id,
            'change_request_text' => 'Name spelling correction on ticket.',
            'status' => 'pending',
            'assigned_at' => now(),
        ]);

        $response = $this->actingAs($this->changesUser)
            ->post(route('changes.update-status', $changeRequest->id), [
                'status' => 'working',
                'changes_remark' => 'Processing correction with airline desk.',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('change_requests', [
            'id' => $changeRequest->id,
            'status' => 'working',
            'changes_remark' => 'Processing correction with airline desk.',
        ]);

        Mail::assertSent(ChangeRequestStatusUpdatedMail::class, function ($mail) {
            return $mail->hasTo('agent@example.com') &&
                   $mail->changeRequest->status === 'working';
        });
    }

    public function test_changes_user_can_update_status_to_completed_and_notify_agent(): void
    {
        Mail::fake();

        $changeRequest = ChangeRequest::create([
            'booking_id' => $this->booking->id,
            'agent_id' => $this->agent->id,
            'change_request_text' => 'Date change.',
            'status' => 'working',
            'assigned_at' => now(),
            'working_at' => now(),
        ]);

        $response = $this->actingAs($this->changesUser)
            ->post(route('changes.update-status', $changeRequest->id), [
                'status' => 'completed',
                'changes_remark' => 'Ticket reissued successfully with new date.',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('change_requests', [
            'id' => $changeRequest->id,
            'status' => 'completed',
            'changes_remark' => 'Ticket reissued successfully with new date.',
        ]);

        Mail::assertSent(ChangeRequestStatusUpdatedMail::class, function ($mail) {
            return $mail->hasTo('agent@example.com') &&
                   $mail->changeRequest->status === 'completed';
        });
    }

    public function test_changes_user_can_upload_attachments_and_update_booking_status(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        Mail::fake();

        $changeRequest = ChangeRequest::create([
            'booking_id' => $this->booking->id,
            'agent_id' => $this->agent->id,
            'change_request_text' => 'Itinerary update with media.',
            'status' => 'pending',
            'assigned_at' => now(),
        ]);

        $fakePdf = \Illuminate\Http\UploadedFile::fake()->create('reissued_ticket.pdf', 100, 'application/pdf');
        $fakeImage = \Illuminate\Http\UploadedFile::fake()->image('screenshot.png');

        $response = $this->actingAs($this->changesUser)
            ->post(route('changes.update-status', $changeRequest->id), [
                'status' => 'completed',
                'booking_status' => 'ticketed',
                'changes_remark' => 'Updated ticket number and attached reissued PDF.',
                'attachments' => [$fakePdf, $fakeImage],
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'booking_status' => 'ticketed',
        ]);

        $changeRequest->refresh();
        $this->assertEquals('completed', $changeRequest->status);
        $this->assertCount(2, $changeRequest->attachments_data);

        $this->assertDatabaseHas('booking_remarks', [
            'booking_id' => $this->booking->id,
            'user_id' => $this->changesUser->id,
            'type' => 'admin_remark',
        ]);
    }

    public function test_extended_change_request_fields_and_statuses(): void
    {
        Mail::fake();

        // 1. Agent submits request with request_type
        $response = $this->actingAs($this->agent)
            ->post(route('bookings.request-change', $this->booking->id), [
                'request_type' => 'Flight Date Change',
                'change_request_text' => 'Shift departure flight to Aug 20.',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('change_requests', [
            'booking_id' => $this->booking->id,
            'request_type' => 'Flight Date Change',
        ]);

        $changeRequest = ChangeRequest::where('booking_id', $this->booking->id)->latest()->first();

        // 2. Changes team processes request with Paid to Airline, FOP, Final Remark, and custom status (Closed)
        $response = $this->actingAs($this->changesUser)
            ->post(route('changes.update-status', $changeRequest->id), [
                'status' => 'closed',
                'paid_to_airline' => 'yes',
                'fop' => 'Credit Card ****9876',
                'final_remark' => 'Reissued with $50 change fee paid.',
                'changes_remark' => 'Agent notified of confirmation number.',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('change_requests', [
            'id' => $changeRequest->id,
            'status' => 'closed',
            'paid_to_airline' => 'yes',
            'fop' => 'Credit Card ****9876',
            'final_remark' => 'Reissued with $50 change fee paid.',
            'closed_by_user_id' => $this->changesUser->id,
        ]);
    }
}
