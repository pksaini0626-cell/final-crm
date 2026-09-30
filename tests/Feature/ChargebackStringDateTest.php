<?php

namespace Tests\Feature;

use App\Models\ChargebackControl;
use App\Models\ChargebackPortal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChargebackStringDateTest extends TestCase
{
    use RefreshDatabase;

    protected User $chargebackUser;
    protected ChargebackControl $chargeback;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'chargeback']);

        $this->chargebackUser = User::create([
            'name' => 'Chargeback Specialist',
            'alias_name' => 'CBK Specialist',
            'email' => 'cbk@example.com',
            'password' => bcrypt('password'),
            'role' => 'chargeback',
            'is_active' => true,
        ]);
        $this->chargebackUser->assignRole('chargeback');

        ChargebackPortal::firstOrCreate(['name' => 'OMT - ALERT']);

        $this->chargeback = ChargebackControl::create([
            'case_number' => 'CBK-TEST-5198',
            'portal' => 'OMT - ALERT',
            'case_type' => 'new',
            'dispute_type' => 'CHARGEBACK',
            'received_date' => '2024-05-10',
            'received_month' => '2024-05',
            'booking_date' => '2024-04-01',
            'booking_month' => '2024-04',
            'deadline_date' => '2024-05-25',
            'action_taken_date' => '2024-05-15',
            'current_status' => 'Chargeback received',
            'cbk_status' => 'Chargeback received',
            'total_booking_amount' => 500.00,
            'disputed_amount' => 500.00,
            'shift_month' => 'Jan--23', // String value from CSV
            'statement_month' => '2024-05', // String value from CSV
            'created_by' => $this->chargebackUser->id,
        ]);
    }

    public function test_edit_page_renders_successfully_with_string_shift_month(): void
    {
        $response = $this->actingAs($this->chargebackUser)
            ->get(route('chargeback.edit', $this->chargeback->id));

        $response->assertStatus(200);
        $response->assertSee('Jan--23');
        $response->assertSee('2024-05');
    }

    public function test_show_page_renders_successfully_with_string_shift_month(): void
    {
        $response = $this->actingAs($this->chargebackUser)
            ->get(route('chargeback.show', $this->chargeback->id));

        $response->assertStatus(200);
        $response->assertSee('Jan--23');
        $response->assertSee('2024-05');
    }

    public function test_export_csv_succeeds_with_string_shift_month(): void
    {
        $response = $this->actingAs($this->chargebackUser)
            ->get(route('chargeback.export'));

        $response->assertStatus(200);
    }

    public function test_update_chargeback_succeeds_with_string_shift_month(): void
    {
        $updateData = [
            'portal' => 'OMT - ALERT',
            'case_number' => 'CBK-TEST-5198',
            'case_type' => 'new',
            'dispute_type' => 'CHARGEBACK',
            'received_date' => '2024-05-10',
            'received_month' => '2024-05',
            'current_status' => 'Represent',
            'disputed_amount' => 500.00,
            'shift_month' => 'Feb--23',
            'statement_month' => '2024-06',
        ];

        $response = $this->actingAs($this->chargebackUser)
            ->put(route('chargeback.update', $this->chargeback->id), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('chargeback_control', [
            'id' => $this->chargeback->id,
            'shift_month' => 'Feb--23',
            'statement_month' => '2024-06',
            'current_status' => 'Represent',
        ]);
    }
}
