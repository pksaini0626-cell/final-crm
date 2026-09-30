<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ChargebackActivity;
use App\Models\ChargebackControl;
use App\Models\ChargebackPortal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MasterAdminChargebackAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $masterAdminUser;
    protected User $adminUser;
    protected User $chargebackUser;
    protected User $agentUser;
    protected ChargebackPortal $portal;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'master_admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'chargeback']);
        Role::firstOrCreate(['name' => 'agent']);

        $this->masterAdminUser = User::create([
            'name' => 'Chief Master Admin',
            'alias_name' => 'MasterAdmin',
            'email' => 'chiefmaster@example.com',
            'password' => bcrypt('password123'),
            'role' => 'master_admin',
            'is_active' => true,
        ]);
        $this->masterAdminUser->assignRole('master_admin');

        $this->adminUser = User::create([
            'name' => 'Standard Admin',
            'alias_name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->adminUser->assignRole('admin');

        $this->chargebackUser = User::create([
            'name' => 'Chargeback Specialist',
            'alias_name' => 'CB Specialist',
            'email' => 'cbspecialist@example.com',
            'password' => bcrypt('password123'),
            'role' => 'chargeback',
            'is_active' => true,
        ]);
        $this->chargebackUser->assignRole('chargeback');

        $this->agentUser = User::create([
            'name' => 'Sales Agent',
            'alias_name' => 'Agent',
            'email' => 'agent@example.com',
            'password' => bcrypt('password123'),
            'role' => 'agent',
            'is_active' => true,
        ]);
        $this->agentUser->assignRole('agent');

        $this->portal = ChargebackPortal::firstOrCreate([
            'name' => 'OMT - ALERT',
        ]);
    }

    public function test_master_admin_can_access_chargeback_analytics_page(): void
    {
        $response = $this->actingAs($this->masterAdminUser)
            ->get(route('master-admin.chargebacks.analytics'));

        $response->assertStatus(200);
        $response->assertSee('Chargeback Analytics &amp; User Footprints', false);
        $response->assertSee('Master Admin Only');
    }

    public function test_standard_admin_is_forbidden_from_master_admin_analytics(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('master-admin.chargebacks.analytics'));

        $response->assertStatus(403);
    }

    public function test_chargeback_user_and_agent_are_forbidden(): void
    {
        $response1 = $this->actingAs($this->chargebackUser)
            ->get(route('master-admin.chargebacks.analytics'));
        $response1->assertStatus(403);

        $response2 = $this->actingAs($this->agentUser)
            ->get(route('master-admin.chargebacks.analytics'));
        $response2->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('master-admin.chargebacks.analytics'));
        $response->assertRedirect(route('login'));
    }

    public function test_chargeback_user_login_creates_footprint_activity(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'cbspecialist@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('chargeback.index'));

        $this->assertDatabaseHas('chargeback_activities', [
            'user_id' => $this->chargebackUser->id,
            'action' => 'login',
        ]);
    }

    public function test_chargeback_creation_records_audit_footprint(): void
    {
        $response = $this->actingAs($this->chargebackUser)->post(route('chargeback.store'), [
            'portal' => 'OMT - ALERT',
            'case_number' => 'CASE-TEST-1001',
            'case_type' => 'new',
            'dispute_type' => 'CHARGEBACK',
            'received_date' => now()->format('Y-m-d'),
            'received_month' => now()->format('Y-m'),
            'deadline_date' => now()->addDays(14)->format('Y-m-d'),
            'action_taken_date' => now()->format('Y-m-d'),
            'current_status' => 'Proceed with chargeback',
            'cbk_status' => 'Represent',
            'disputed_amount' => 450.00,
            'currency' => 'USD',
            'pnr' => 'TEST01',
        ]);

        $response->assertRedirect(route('chargeback.index'));

        $this->assertDatabaseHas('chargeback_activities', [
            'user_id' => $this->chargebackUser->id,
            'case_number' => 'CASE-TEST-1001',
            'action' => 'created',
            'pnr' => 'TEST01',
        ]);
    }

    public function test_chargeback_update_records_granular_field_differences(): void
    {
        $cb = ChargebackControl::create([
            'portal' => 'OMT - ALERT',
            'case_number' => 'CASE-DIFF-2002',
            'case_type' => 'new',
            'dispute_type' => 'ALERT',
            'received_date' => now()->format('Y-m-d'),
            'received_month' => now()->format('Y-m'),
            'deadline_date' => now()->addDays(10)->format('Y-m-d'),
            'action_taken_date' => now()->format('Y-m-d'),
            'current_status' => 'Chargeback received',
            'cbk_status' => 'ALERT',
            'disputed_amount' => 500.00,
            'currency' => 'USD',
            'created_by' => $this->chargebackUser->id,
        ]);

        $response = $this->actingAs($this->chargebackUser)->put(route('chargeback.update', $cb->id), [
            'portal' => 'OMT - ALERT',
            'case_number' => 'CASE-DIFF-2002',
            'case_type' => 'new',
            'dispute_type' => 'CHARGEBACK', // Changed from ALERT
            'received_date' => now()->format('Y-m-d'),
            'received_month' => now()->format('Y-m'),
            'deadline_date' => now()->addDays(10)->format('Y-m-d'),
            'action_taken_date' => now()->format('Y-m-d'),
            'current_status' => 'Won', // Changed from Chargeback received
            'cbk_status' => 'Won',
            'disputed_amount' => 750.00, // Changed from 500.00
            'currency' => 'USD',
        ]);

        $response->assertRedirect(route('chargeback.index'));

        $activity = ChargebackActivity::where('case_number', 'CASE-DIFF-2002')
            ->where('action', 'updated')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals($this->chargebackUser->id, $activity->user_id);
        $this->assertArrayHasKey('dispute_type', $activity->changes);
        $this->assertEquals('ALERT', $activity->changes['dispute_type']['old']);
        $this->assertEquals('CHARGEBACK', $activity->changes['dispute_type']['new']);
        $this->assertArrayHasKey('disputed_amount', $activity->changes);
    }

    public function test_master_admin_analytics_displays_footprints_and_today_work(): void
    {
        // 1. Create a chargeback
        $cb = ChargebackControl::create([
            'portal' => 'OMT - ALERT',
            'case_number' => 'CASE-VIEW-3003',
            'case_type' => 'new',
            'dispute_type' => 'CHARGEBACK',
            'received_date' => now()->format('Y-m-d'),
            'received_month' => now()->format('Y-m'),
            'deadline_date' => now()->addDays(7)->format('Y-m-d'),
            'action_taken_date' => now()->format('Y-m-d'),
            'current_status' => 'Chargeback received',
            'cbk_status' => 'Represent',
            'disputed_amount' => 300.00,
            'currency' => 'USD',
            'created_by' => $this->chargebackUser->id,
            'created_at' => now(),
        ]);

        // 2. Record an activity today
        ChargebackActivity::record([
            'user_id' => $this->chargebackUser->id,
            'chargeback_id' => $cb->id,
            'case_number' => $cb->case_number,
            'action' => 'updated',
            'description' => 'Updated status to Won',
            'changes' => ['current_status' => ['old' => 'Chargeback received', 'new' => 'Won']],
        ]);

        $response = $this->actingAs($this->masterAdminUser)
            ->get(route('master-admin.chargebacks.analytics'));

        $response->assertStatus(200);
        $response->assertSee('CASE-VIEW-3003');
        $response->assertSee('Chargeback Specialist');
        $response->assertSee('Updated status to Won');
    }
}
