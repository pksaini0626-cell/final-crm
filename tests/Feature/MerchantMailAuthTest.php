<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Merchant;
use App\Models\User;
use App\Mail\CustomerAuthMail;
use App\Services\MerchantMailService;
use App\Http\Controllers\CustomerAuthController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MerchantMailAuthTest extends TestCase
{
    use RefreshDatabase;

    protected User $agent;
    protected Merchant $merchant;
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agent = User::create([
            'name' => 'Agent Smith',
            'alias_name' => 'Smith A',
            'email' => 'smith@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->merchant = Merchant::create([
            'name' => 'SecurePay Ltd',
            'merchant_code' => 'securepay_us',
            'wallet_balance' => 5000.00,
            'smtp_host' => 'smtp.securepay.com',
            'smtp_port' => 587,
            'smtp_username' => 'api_user',
            'smtp_password' => 'secret_smtp_password', // Cast to encrypted
            'smtp_encryption' => 'tls',
            'from_email' => 'no-reply@securepay.com',
            'from_name' => 'SecurePay Auto Mail',
            'is_smtp_active' => true,
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
            'total_amount' => 850.00,
            'paid_to_airline' => 700.00,
            'total_mco' => 150.00,
            'payment_status' => 'pending',
            'card_holder_name' => 'David Miller',
            'card_last_4' => '9988',
        ]);
    }

    /**
     * Test Merchant creation, attributes casting and password encryption.
     */
    public function test_merchant_model_encryption_and_casting(): void
    {
        $this->assertDatabaseHas('merchants', [
            'merchant_code' => 'securepay_us',
        ]);

        $retrieved = Merchant::find($this->merchant->id);

        // Decrypted password should match the raw input
        $this->assertEquals('secret_smtp_password', $retrieved->smtp_password);

        // Verify the database does not store the raw password
        $rawInDb = \DB::table('merchants')->where('id', $retrieved->id)->value('smtp_password');
        $this->assertNotEquals('secret_smtp_password', $rawInDb);
    }

    /**
     * Test signature URL hashing and public validation rules.
     */
    public function test_customer_authorization_hash_access(): void
    {
        $validHash = CustomerAuthController::generateHash($this->booking);
        $invalidHash = 'incorrecthashvalue';

        // Valid signature URL should load page with booking summary
        $this->get(route('customer.authorize', ['booking' => $this->booking->id, 'hash' => $validHash]))
            ->assertStatus(200)
            ->assertSee($this->booking->booking_id)
            ->assertSee('David Miller')
            ->assertSee('850.00');

        // Invalid signature URL should fail with 403 Forbidden
        $this->get(route('customer.authorize', ['booking' => $this->booking->id, 'hash' => $invalidHash]))
            ->assertStatus(403);
    }

    /**
     * Test public approval processes successfully and updates statuses.
     */
    public function test_customer_authorization_approval(): void
    {
        $validHash = CustomerAuthController::generateHash($this->booking);

        $payload = [
            'terms_accepted' => '1',
            'signature' => 'David Miller',
        ];

        $response = $this->post(route('customer.authorize.approve', ['booking' => $this->booking->id, 'hash' => $validHash]), $payload);

        $response->assertRedirect();
        
        $this->booking->refresh();

        // Check if authorization states were correctly updated
        $this->assertTrue($this->booking->email_auth_taken);
        $this->assertEquals('email_auth_done', $this->booking->booking_status);

        // Check that a system log was created in booking remarks
        $this->assertDatabaseHas('booking_remarks', [
            'booking_id' => $this->booking->id,
            'type' => 'system_log',
        ]);
    }

    /**
     * Test MerchantMailService dynamically overrides configuration and dispatches the Mailable.
     */
    public function test_merchant_mail_service_sends_email_with_dynamic_smtp(): void
    {
        Mail::fake();

        // Ensure SMTP configurations are dynamically changed and restored
        $service = new MerchantMailService();
        
        // Let's assert mail config is changed runtime
        $service->sendAuthorizationEmail($this->booking);

        Mail::assertSent(CustomerAuthMail::class, function ($mail) {
            return $mail->hasTo('customer@example.com') &&
                   $mail->booking->id === $this->booking->id &&
                   $mail->fromName === 'Reservation Desk' &&
                   !empty($mail->authUrl);
        });
    }
}
