<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Merchant;
use App\Models\User;
use App\Mail\CustomerAuthMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LiveAuthEmailEditorTest extends TestCase
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
            'role' => 'agent',
        ]);

        $this->merchant = Merchant::create([
            'name' => 'Travelomile',
            'merchant_code' => 'travelomile_us',
            'wallet_balance' => 5000.00,
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_username' => 'api_user',
            'smtp_password' => 'secret_smtp_password',
            'smtp_encryption' => 'tls',
            'from_email' => 'reservation@travelomile.com',
            'from_name' => 'Reservation Desk',
            'is_smtp_active' => true,
            'currency' => 'USD',
        ]);

        $this->booking = Booking::create([
            'agent_id' => $this->agent->id,
            'merchant_id' => $this->merchant->id,
            'booking_date' => '2026-08-07',
            'vertical' => 'flight',
            'service_provided' => 'new_booking',
            'booking_portal' => 'website',
            'language' => 'English',
            'email_address' => 'customer@example.com',
            'booking_status' => 'booking_generated',
            'currency' => 'USD',
            'total_amount' => 850.00,
            'card_holder_name' => 'Jane Doe',
            'card_last_4' => '1234',
        ]);
    }

    /**
     * Test live preview page loads successfully with rich text editor container.
     */
    public function test_auth_email_preview_page_loads_editor(): void
    {
        $response = $this->actingAs($this->agent)->get(route('bookings.auth-email.preview', $this->booking));
        $response->assertStatus(200);
        $response->assertSee('Rich Text Email Editor');
        $response->assertSee('id="email-preview-container"', false);
        $response->assertSee('contenteditable="true"', false);
    }

    /**
     * Test submitting custom edited HTML dispatches CustomerAuthMail containing the custom HTML.
     */
    public function test_sending_edited_custom_html_email(): void
    {
        Mail::fake();

        $customHtmlContent = '<div style="background-color:#ffffff;"><h1>Custom Edited Authorization Header</h1><p>Dear Jane Doe, this is a custom edited authorization template body.</p></div>';

        $payload = [
            'email_address' => 'customer@example.com',
            'subject' => 'Custom Edited Authorization Subject',
            'email_language' => 'english',
            'from_name' => 'Reservation Desk',
            'from_email' => 'reservation@travelomile.com',
            'agent_name' => 'Agent Smith',
            'agent_ext' => '187',
            'custom_html' => $customHtmlContent,
        ];

        $response = $this->actingAs($this->agent)->post(route('bookings.auth-email.send', $this->booking), $payload);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('bookings.index'));

        // Assert mail was sent with custom HTML
        Mail::assertSent(CustomerAuthMail::class, function ($mail) use ($customHtmlContent) {
            return $mail->hasTo('customer@example.com') &&
                   $mail->customSubject === 'Custom Edited Authorization Subject' &&
                   $mail->customHtml === $customHtmlContent;
        });

        // Verify status updated
        $this->booking->refresh();
        $this->assertEquals('email_auth_sent', $this->booking->booking_status);
    }
}
