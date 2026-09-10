<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\PaymentLink;
use App\Models\User;
use App\Services\NmiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantPaymentChargeTest extends TestCase
{
    use RefreshDatabase;

    public function test_nmi_service_fallback_to_config(): void
    {
        config([
            'nmi.security_key' => 'global_security_key',
            'nmi.api_url' => 'https://global.gateway.com/transact.php',
            'nmi.tokenization_key' => 'global_token_key',
        ]);

        $service = new NmiService();
        $this->assertEquals('global_security_key', $service->getSecurityKey());
        $this->assertEquals('https://global.gateway.com/transact.php', $service->getApiUrl());
    }

    public function test_nmi_service_uses_merchant_overrides(): void
    {
        $merchant = Merchant::create([
            'name' => 'Custom Merchant',
            'merchant_code' => 'CM001',
            'security_key' => 'merchant_sec_key',
            'api_url' => 'https://merchant.gateway.com/transact.php',
            'tokenization_key' => 'merchant_tok_key',
            'wallet_balance' => 0,
            'currency' => 'USD',
        ]);

        $service = new NmiService();
        $service->useMerchant($merchant);

        $this->assertEquals('merchant_sec_key', $service->getSecurityKey());
        $this->assertEquals('https://merchant.gateway.com/transact.php', $service->getApiUrl());
        $this->assertEquals('merchant_tok_key', $service->getTokenizationKey());
        $this->assertEquals($merchant->id, $service->getMerchantId());
    }

    public function test_payment_link_creation_and_public_access(): void
    {
        $merchant = Merchant::create([
            'name' => 'Test Merchant',
            'merchant_code' => 'TM001',
            'security_key' => 'sec_key_123',
            'api_url' => 'https://macpayments.transactiongateway.com/api/transact.php',
            'wallet_balance' => 0,
            'currency' => 'USD',
        ]);

        $paymentLink = PaymentLink::create([
            'merchant_id' => $merchant->id,
            'customer_first_name' => 'John',
            'customer_last_name' => 'Doe',
            'email' => 'john@example.com',
            'amount' => 150.00,
            'currency' => 'USD',
            'status' => 'pending',
            'expires_at' => now()->addHours(48),
        ]);

        $this->assertNotNull($paymentLink->token);
        $this->assertFalse($paymentLink->isExpired());

        $response = $this->get('/pay/' . $paymentLink->token);
        $response->assertStatus(200);
        $response->assertSee('John');
        $response->assertSee('150.00');
    }
}
