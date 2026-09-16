<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class McoReportRulesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'alias_name' => 'Admin User',
            'is_active' => true,
        ]);

        $this->agent = User::factory()->create([
            'role' => 'agent',
            'alias_name' => 'Agent User',
            'is_active' => true,
        ]);
    }

    public function test_booking_model_reportable_mco_logic(): void
    {
        $today = Carbon::today()->format('Y-m-d');

        // 1. booking_generated with received payment -> NOT reportable
        $b1 = Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => $today,
            'service_provided' => 'flight_booking',
            'booking_status' => 'booking_generated',
            'payment_status' => 'received',
            'total_amount' => 500,
            'total_mco' => 100,
            'email_address' => 'test1@example.com',
        ]);
        $this->assertFalse($b1->isReportableMco());
        $this->assertEquals(0.0, $b1->reportable_mco);

        // 2. ticketed with pending payment -> gets auto-converted to received by model event!
        $b2 = Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => $today,
            'service_provided' => 'flight_booking',
            'booking_status' => 'ticketed',
            'payment_status' => 'pending',
            'total_amount' => 500,
            'total_mco' => 150,
            'email_address' => 'test2@example.com',
        ]);
        // Auto-conversion sets payment_status to received and email_auth_taken to true
        $this->assertEquals('received', $b2->fresh()->payment_status);
        $this->assertTrue($b2->fresh()->email_auth_taken);
        $this->assertTrue($b2->fresh()->isReportableMco());
        $this->assertEquals(150.0, $b2->fresh()->reportable_mco);

        // 3. ticketed with cancelled payment -> NOT reportable
        $b3 = Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => $today,
            'service_provided' => 'flight_booking',
            'booking_status' => 'ticketed',
            'payment_status' => 'cancelled',
            'total_amount' => 500,
            'total_mco' => 200,
            'email_address' => 'test3@example.com',
        ]);
        $this->assertFalse($b3->isReportableMco());
        $this->assertEquals(0.0, $b3->reportable_mco);

        // 4. ticketed with refund payment -> NOT reportable
        $b4 = Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => $today,
            'service_provided' => 'flight_booking',
            'booking_status' => 'ticketed',
            'payment_status' => 'refund',
            'total_amount' => 500,
            'total_mco' => 250,
            'email_address' => 'test4@example.com',
        ]);
        $this->assertFalse($b4->isReportableMco());
        $this->assertEquals(0.0, $b4->reportable_mco);
    }

    public function test_daily_report_calculates_only_reportable_mco(): void
    {
        $today = Carbon::today()->format('Y-m-d');

        // Valid reportable booking (ticketed + received)
        Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => $today,
            'service_provided' => 'flight_booking',
            'booking_status' => 'ticketed',
            'payment_status' => 'received',
            'currency' => 'USD',
            'total_amount' => 1000,
            'total_mco' => 200,
            'email_address' => 'valid@example.com',
        ]);

        // Non-reportable booking (booking_generated + pending)
        Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => $today,
            'service_provided' => 'flight_booking',
            'booking_status' => 'booking_generated',
            'payment_status' => 'pending',
            'currency' => 'USD',
            'total_amount' => 500,
            'total_mco' => 100,
            'email_address' => 'gen@example.com',
        ]);

        // Non-reportable booking (ticketed + refund)
        Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => $today,
            'service_provided' => 'flight_booking',
            'booking_status' => 'ticketed',
            'payment_status' => 'refund',
            'currency' => 'USD',
            'total_amount' => 600,
            'total_mco' => 150,
            'email_address' => 'refund@example.com',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.daily'));
        $response->assertStatus(200);

        $breakdowns = $response->viewData('currencyBreakdowns');
        $this->assertArrayHasKey($today, $breakdowns);
        $usdBreakdown = collect($breakdowns[$today])->firstWhere('currency', 'USD');
        
        // Total MCO should ONLY count the valid reportable booking (200.00), excluding 100 and 150
        $this->assertEquals(200.00, $usdBreakdown['total_mco']);
    }

    public function test_admin_dashboard_mco_metrics_honor_reportable_rules(): void
    {
        $today = Carbon::today()->format('Y-m-d');

        // Ticketed + Received -> 300 MCO
        Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => $today,
            'service_provided' => 'flight_booking',
            'booking_status' => 'ticketed',
            'payment_status' => 'received',
            'currency' => 'USD',
            'total_amount' => 1500,
            'total_mco' => 300,
            'email_address' => 'dash1@example.com',
        ]);

        // Booking Generated + Received -> 100 MCO (excluded)
        Booking::create([
            'agent_id' => $this->agent->id,
            'booking_date' => $today,
            'service_provided' => 'flight_booking',
            'booking_status' => 'booking_generated',
            'payment_status' => 'received',
            'currency' => 'USD',
            'total_amount' => 500,
            'total_mco' => 100,
            'email_address' => 'dash2@example.com',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);

        $response->assertViewHas('todayTotalMco', 300.0);
    }
}
