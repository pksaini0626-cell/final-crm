<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MerchantChargeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure test user exists
        User::updateOrCreate(
            ['email' => 'charge@callinggenie.com'],
            [
                'name'       => 'Merchant Charge Desk',
                'alias_name' => 'Charge Terminal',
                'password'   => Hash::make('Charge@123#'),
                'role'       => 'admin',
                'is_active'  => true,
            ]
        );
    }

    public function test_unauthenticated_user_sees_login_view(): void
    {
        $response = $this->get('/merchentcharge');

        $response->assertStatus(200);
        $response->assertSee('Merchant Charge Terminal');
        $response->assertSee('Terminal Operator Email');
    }

    public function test_user_can_login_to_merchant_charge_terminal(): void
    {
        $response = $this->post('/merchentcharge/login', [
            'email'    => 'charge@callinggenie.com',
            'password' => 'Charge@123#',
        ]);

        $response->assertRedirect('/merchentcharge');
        $this->assertAuthenticated();
    }

    public function test_authenticated_user_can_access_terminal_dashboard(): void
    {
        $user = User::where('email', 'charge@callinggenie.com')->first();
        $this->actingAs($user);

        $merchant = Merchant::create([
            'name'           => 'Test Merchant Gateway',
            'merchant_code'  => 'TST001',
            'security_key'   => '2F822rw2945617KBE77ZDhqqUP43RMro',
            'api_url'        => 'https://secure.nmi.com/api/transact.php',
            'currency'       => 'USD',
            'is_active'      => true,
        ]);

        $response = $this->get('/merchentcharge');

        $response->assertStatus(200);
        $response->assertSee('Test Merchant Gateway');
        $response->assertSee('STEP 1');
        $response->assertSee('STEP 2');
        $response->assertSee('Payment info');
    }

    public function test_booking_search_returns_payment_info_and_details(): void
    {
        $user = User::where('email', 'charge@callinggenie.com')->first();
        $this->actingAs($user);

        $booking = Booking::create([
            'booking_id'       => 'TST9988',
            'agent_id'         => $user->id,
            'booking_date'     => now(),
            'service_provided' => 'new_booking',
            'airline_pnr'      => 'PNR8899',
            'card_holder_name' => 'Alice Johnson',
            'email_address'    => 'alice@example.com',
            'billing_phone'    => '5551234567',
            'total_amount'     => 450.00,
            'payment_status'   => 'pending',
            'payment_info'     => 'Special instructions: Charge 450 upon ticket confirmation',
        ]);

        $response = $this->getJson('/merchentcharge/search-bookings?q=PNR8899');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonFragment([
            'airline_pnr'  => 'PNR8899',
            'payment_info' => 'Special instructions: Charge 450 upon ticket confirmation',
        ]);
    }

    public function test_successful_nmi_direct_charge_updates_booking_and_payment_info(): void
    {
        $user = User::where('email', 'charge@callinggenie.com')->first();
        $this->actingAs($user);

        $merchant = Merchant::create([
            'name'           => 'Demo Merchant',
            'merchant_code'  => 'DEMO1',
            'security_key'   => 'demo_key_123',
            'api_url'        => 'https://secure.nmi.com/api/transact.php',
            'currency'       => 'USD',
            'wallet_balance' => 5000.00,
            'is_active'      => true,
        ]);

        $booking = Booking::create([
            'booking_id'       => 'BKCHG01',
            'agent_id'         => $user->id,
            'booking_date'     => now(),
            'service_provided' => 'new_booking',
            'airline_pnr'      => 'AIR777',
            'card_holder_name' => 'Bob Smith',
            'email_address'    => 'bob@example.com',
            'total_amount'     => 300.00,
            'payment_status'   => 'pending',
            'payment_info'     => 'Initial booking remarks note',
        ]);

        // Mock NMI approval response
        Http::fake([
            'https://secure.nmi.com/api/transact.php*' => Http::response(
                'response=1&responsetext=SUCCESS&authcode=998877&transactionid=1122334455&avsresponse=Y&cvvresponse=M&orderid=BK-1-test&type=sale&response_code=100',
                200
            ),
        ]);

        $response = $this->postJson('/merchentcharge/process', [
            'merchant_id'         => $merchant->id,
            'booking_id'          => $booking->id,
            'amount'              => 300.00,
            'ccnumber'            => '4007000000027',
            'ccexp'               => '12/28',
            'cvv'                 => '123',
            'customer_first_name' => 'Bob',
            'customer_last_name'  => 'Smith',
            'phone'               => '5559876543',
            'payment_info_note'   => 'Customer approved full payment by phone',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status'  => 'approved',
        ]);
        $response->assertJsonFragment([
            'transaction_id'          => '1122334455',
            'auth_code'               => '998877',
            'merchant_wallet_balance' => '4,700.00',
        ]);

        // Check DB updates
        $booking->refresh();
        $merchant->refresh();
        $this->assertEquals(4700.00, (float) $merchant->wallet_balance);
        $this->assertEquals('received', $booking->payment_status);
        $this->assertStringContainsString('Charged $300.00 via NMI', $booking->payment_info);
        $this->assertStringContainsString('Customer approved full payment by phone', $booking->payment_info);
    }
}
