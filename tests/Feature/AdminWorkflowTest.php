<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent;
    protected Merchant $merchant;
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'System Admin',
            'alias_name' => 'Admin Boss',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->agent = User::create([
            'name' => 'Agent Smith',
            'alias_name' => 'Smith A',
            'email' => 'smith@example.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
        ]);

        $this->merchant = Merchant::create([
            'name' => 'Global Pay',
            'merchant_code' => 'global_pay',
            'wallet_balance' => 10000.00,
            'currency' => 'USD',
        ]);

        $this->booking = Booking::create([
            'agent_id' => $this->agent->id,
            'merchant_id' => $this->merchant->id,
            'booking_date' => '2026-08-04',
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'language' => 'English',
            'email_address' => 'customer@example.com',
            'booking_status' => 'booking_generated',
            'currency' => 'USD',
            'total_amount' => 1200.00,
            'paid_to_airline' => 1000.00,
            'total_mco' => 200.00,
            'payment_status' => 'pending',
            'card_holder_name' => 'Michael Scott',
            'card_last_4' => '1234',
        ]);
    }

    /**
     * Test role-based protection for admin endpoints.
     */
    public function test_admin_routes_restricted_to_admin_role(): void
    {
        // Agent trying to access admin panel -> 403 Forbidden
        $this->actingAs($this->agent)
            ->get(route('admin.users.index'))
            ->assertStatus(403);

        $this->actingAs($this->agent)
            ->get(route('admin.bookings.index'))
            ->assertStatus(403);

        // Admin accessing admin panel -> 200 OK
        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertStatus(200);

        $this->actingAs($this->admin)
            ->get(route('admin.bookings.index'))
            ->assertStatus(200);
    }

    public function test_admin_user_create_page_loads(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.create');
    }

    public function test_admin_user_store_validation_redirects_with_errors(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.store'), []);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['name', 'alias_name', 'email', 'password', 'role']);
    }

    /**
     * Test User CRUD operations and active status toggling.
     */
    public function test_admin_user_crud_and_status_toggle(): void
    {
        // Create user
        $userPayload = [
            'name' => 'Robert California',
            'alias_name' => 'Bob C',
            'email' => 'bob@example.com',
            'password' => 'secret123',
            'role' => 'manager',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.store'), $userPayload);

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'bob@example.com',
            'alias_name' => 'Bob C',
            'role' => 'manager',
        ]);

        $user = User::where('email', 'bob@example.com')->first();

        // Toggle active status (deactivate)
        $this->actingAs($this->admin)
            ->post(route('admin.users.toggle-active', $user));

        $user->refresh();
        $this->assertFalse($user->is_active);
    }

    /**
     * Test Admin can create, edit, and filter user profiles with MIS role.
     */
    public function test_admin_can_create_and_manage_mis_user(): void
    {
        // Create MIS user
        $payload = [
            'name' => 'MIS Agent One',
            'alias_name' => 'MIS Agent 1',
            'email' => 'misagent@example.com',
            'password' => 'password123',
            'role' => 'mis',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.store'), $payload);

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'misagent@example.com',
            'alias_name' => 'MIS Agent 1',
            'role' => 'mis',
        ]);

        $misUser = User::where('email', 'misagent@example.com')->first();

        // Update MIS user
        $updatePayload = [
            'name' => 'MIS Agent Updated',
            'alias_name' => 'MIS Agent 1 Updated',
            'email' => 'misagent@example.com',
            'role' => 'mis',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.users.update', $misUser), $updatePayload);

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $misUser->id,
            'name' => 'MIS Agent Updated',
            'role' => 'mis',
        ]);

        // Filter user directory by MIS role
        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['role' => 'mis']));

        $response->assertStatus(200);
        $response->assertSee('MIS Agent Updated');
    }

    /**
     * Test Admin multi-filtering and case status updates.
     */
    public function test_admin_booking_filtering_and_case_status_management(): void
    {
        // Filter by agent and search
        $response = $this->actingAs($this->admin)
            ->get(route('admin.bookings.index', [
                'agent_id' => $this->agent->id,
                'q' => 'Michael',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Michael Scott');

        // Update case status to chargeback
        $response = $this->actingAs($this->admin)
            ->post(route('admin.bookings.update-case-status', $this->booking), [
                'case_status' => 'chargeback',
                'update_booking_status_to_void' => '1',
            ]);

        $response->assertRedirect();

        $this->booking->refresh();
        $this->assertEquals('chargeback', $this->booking->case_status);
        $this->assertEquals('void', $this->booking->booking_status);

        // Verify admin remark logged
        $this->assertDatabaseHas('booking_remarks', [
            'booking_id' => $this->booking->id,
            'user_id' => $this->admin->id,
            'type' => 'admin_remark',
        ]);
    }

    /**
     * Test CSV Export functionality.
     */
    public function test_csv_export_endpoint_streams_correct_data(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.bookings.export', [
                'service_provided' => 'new_booking',
            ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        // Obtain streamed content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('Booking ID', $content);
        $this->assertStringContainsString('Agent Alias', $content);
        $this->assertStringContainsString($this->booking->booking_id, $content);
        $this->assertStringContainsString('Smith A', $content);
    }

    /**
     * Test admin booking deletion.
     */
    public function test_admin_can_delete_booking(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.bookings.destroy', $this->booking));

        $response->assertRedirect(route('admin.bookings.index'));

        $this->assertDatabaseMissing('bookings', [
            'id' => $this->booking->id,
        ]);
    }

    /**
     * Test admin permanent user deletion.
     */
    public function test_admin_can_permanently_delete_user(): void
    {
        $userToDelete = User::create([
            'name' => 'Temporary User',
            'alias_name' => 'Temp U',
            'email' => 'temp@example.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $userToDelete));

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $userToDelete->id,
        ]);
    }

    /**
     * Test is_active radio button values (1, 0, true, false) and form rendering.
     */
    public function test_admin_user_active_radio_button_states_and_validation(): void
    {
        // 1. Verify create form renders active and inactive radio buttons
        $createPageResponse = $this->actingAs($this->admin)
            ->get(route('admin.users.create'));
        $createPageResponse->assertStatus(200);
        $createPageResponse->assertSee('name="is_active"', false);
        $createPageResponse->assertSee('value="1"', false);
        $createPageResponse->assertSee('value="0"', false);

        // 2. Create user with Inactive radio button (value = "0")
        $inactiveUserPayload = [
            'name' => 'Inactive Agent',
            'alias_name' => 'Inact A',
            'email' => 'inact@example.com',
            'password' => 'password123',
            'role' => 'agent',
            'is_active' => '0',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.store'), $inactiveUserPayload);
        $response->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'inact@example.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse((bool) $user->is_active);

        // 3. Update user to Active with radio button (value = "1")
        $updatePayload = [
            'name' => 'Inactive Agent',
            'alias_name' => 'Inact A',
            'email' => 'inact@example.com',
            'role' => 'agent',
            'is_active' => '1',
        ];
        $response = $this->actingAs($this->admin)
            ->put(route('admin.users.update', $user), $updatePayload);
        $response->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertTrue((bool) $user->is_active);

        // 4. Update user with "0" to deactivate
        $updatePayload['is_active'] = '0';
        $response = $this->actingAs($this->admin)
            ->put(route('admin.users.update', $user), $updatePayload);
        $response->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertFalse((bool) $user->is_active);

        // 5. Verify string values like "true" and "false" do not fail validation
        $updatePayload['is_active'] = 'true';
        $response = $this->actingAs($this->admin)
            ->put(route('admin.users.update', $user), $updatePayload);
        $response->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertTrue((bool) $user->is_active);

        $updatePayload['is_active'] = 'false';
        $response = $this->actingAs($this->admin)
            ->put(route('admin.users.update', $user), $updatePayload);
        $response->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertFalse((bool) $user->is_active);
    }
}

