<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookingStatusQuickUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected User $agent;
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'manager']);
        Role::create(['name' => 'agent']);

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'role' => 'admin',
            'alias_name' => 'Admin',
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->manager = User::factory()->create([
            'name' => 'Manager User',
            'role' => 'manager',
            'alias_name' => 'Manager',
            'is_active' => true,
        ]);
        $this->manager->assignRole('manager');

        $this->agent = User::factory()->create([
            'name' => 'Agent User',
            'role' => 'agent',
            'alias_name' => 'Agent',
            'is_active' => true,
        ]);
        $this->agent->assignRole('agent');

        $this->booking = Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => Carbon::today()->format('Y-m-d'),
            'service_provided' => 'flight_booking',
            'booking_status' => 'booking_generated',
            'payment_status' => 'pending',
            'total_amount' => 650.00,
            'paid_to_airline' => 500.00,
            'total_mco' => 150.00,
            'email_address' => 'customer@example.com',
            'airline_pnr' => 'XYZ789',
            'card_holder_name' => 'John Traveler',
        ]);
    }

    public function test_admin_sees_quick_status_icon_in_bookings_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('bookings.index'));

        $response->assertStatus(200);
        $response->assertSee('Status directly');
        $response->assertSee('bi-pencil-square');
    }

    public function test_agent_does_not_see_quick_status_icon(): void
    {
        $response = $this->actingAs($this->agent)->get(route('bookings.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Status directly');
    }

    public function test_admin_can_update_booking_and_payment_status_directly(): void
    {
        $response = $this->actingAs($this->admin)->post(route('bookings.update-status', $this->booking), [
            'booking_status' => 'ticketed',
            'payment_status' => 'received',
            'remark' => 'Verified ticket issued and payment captured.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->booking->refresh();
        $this->assertEquals('ticketed', $this->booking->booking_status);
        $this->assertEquals('received', $this->booking->payment_status);
        $this->assertTrue($this->booking->email_auth_taken);

        // Check audit remark logged
        $this->assertDatabaseHas('booking_remarks', [
            'booking_id' => $this->booking->id,
            'user_id' => $this->admin->id,
            'type' => 'admin_remark',
        ]);

        $remark = $this->booking->bookingRemarks()->latest()->first();
        $this->assertStringContainsString('Booking Status', $remark->remark);
        $this->assertStringContainsString('TICKETED', $remark->remark);
        $this->assertStringContainsString('Verified ticket issued and payment captured.', $remark->remark);
    }

    public function test_manager_can_also_update_booking_status(): void
    {
        $response = $this->actingAs($this->manager)->post(route('bookings.update-status', $this->booking), [
            'booking_status' => 'email_auth_done',
            'payment_status' => 'pending',
            'remark' => 'Auth received from customer signature.',
        ]);

        $response->assertRedirect();
        $this->booking->refresh();
        $this->assertEquals('email_auth_done', $this->booking->booking_status);
    }

    public function test_regular_agent_cannot_update_status(): void
    {
        $response = $this->actingAs($this->agent)->post(route('bookings.update-status', $this->booking), [
            'booking_status' => 'booking_complete',
            'payment_status' => 'received',
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_update_status(): void
    {
        $response = $this->post(route('bookings.update-status', $this->booking), [
            'booking_status' => 'ticketed',
            'payment_status' => 'received',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_status_update_validates_allowed_statuses(): void
    {
        $response = $this->actingAs($this->admin)->post(route('bookings.update-status', $this->booking), [
            'booking_status' => 'invalid_status',
            'payment_status' => 'invalid_payment',
        ]);

        $response->assertSessionHasErrors(['booking_status', 'payment_status']);
    }

    public function test_ajax_json_request_returns_json_response(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('bookings.update-status', $this->booking), [
            'booking_status' => 'booking_complete',
            'payment_status' => 'received',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'booking_status' => 'booking_complete',
            'payment_status' => 'received',
        ]);
    }
}
