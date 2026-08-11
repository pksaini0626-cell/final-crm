<?php

namespace Tests\Feature;

use App\Models\CallLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CallLogWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'alias_name' => 'Admin User',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->agent = User::factory()->create([
            'alias_name' => 'Agent User',
            'role' => 'agent',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_call_logs(): void
    {
        $response = $this->get(route('call-logs.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_agent_can_create_and_view_own_call_logs(): void
    {
        $payload = [
            'customer_name' => 'Alice Smith',
            'phone_number' => '+1555999000',
            'email' => 'alice@example.com',
            'city' => 'Chicago',
            'service_provided' => 'new_booking',
            'follow_up' => 1,
            'call_date' => '2026-08-07 10:30:00',
            'remark' => 'Inquired about flights to London.',
        ];

        $response = $this->actingAs($this->agent)
            ->post(route('call-logs.store'), $payload);

        $response->assertRedirect(route('call-logs.index'));

        $this->assertDatabaseHas('call_logs', [
            'agent_id' => $this->agent->id,
            'customer_name' => 'Alice Smith',
            'phone_number' => '+1555999000',
            'city' => 'Chicago',
            'service_provided' => 'new_booking',
            'follow_up' => true,
        ]);

        $indexResponse = $this->actingAs($this->agent)
            ->get(route('call-logs.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('Alice Smith');
        $indexResponse->assertSee('New Booking');
    }

    public function test_admin_can_view_all_call_logs_and_filter_by_agent_and_service_provided(): void
    {
        CallLog::create([
            'agent_id' => $this->agent->id,
            'customer_name' => 'Bob Johnson',
            'phone_number' => '+1555111222',
            'email' => 'bob@example.com',
            'city' => 'Dallas',
            'service_provided' => 'cancellation',
            'follow_up' => false,
            'call_date' => '2026-08-07 11:00:00',
            'remark' => 'Flight quote provided.',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('call-logs.index', ['agent_id' => $this->agent->id, 'service_provided' => 'cancellation']));

        $response->assertOk();
        $response->assertSee('Bob Johnson');
        $response->assertSee('Cancellation');
    }

    public function test_call_log_csv_export_endpoint(): void
    {
        $log = CallLog::create([
            'agent_id' => $this->agent->id,
            'customer_name' => 'Charlie Brown',
            'phone_number' => '+1555333444',
            'email' => 'charlie@example.com',
            'city' => 'Miami',
            'service_provided' => 'flight_upgrade',
            'follow_up' => true,
            'call_date' => '2026-08-07 14:00:00',
            'remark' => 'Follow up on booking status.',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('call-logs.export', ['ids' => $log->id]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Charlie Brown', $response->streamedContent());
        $this->assertStringContainsString('Flight Upgrade', $response->streamedContent());
    }

    public function test_agent_can_delete_own_call_log(): void
    {
        $log = CallLog::create([
            'agent_id' => $this->agent->id,
            'customer_name' => 'David Miller',
            'phone_number' => '+1555777888',
            'follow_up' => false,
            'call_date' => '2026-08-07 16:00:00',
        ]);

        $response = $this->actingAs($this->agent)
            ->delete(route('call-logs.destroy', $log->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('call_logs', ['id' => $log->id]);
    }
}
