<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Merchant;
use App\Mail\CustomerAuthMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class MerchantMailService
{
    /**
     * Configure mailer dynamically based on Merchant SMTP settings and send the Auth Email.
     *
     * @param Booking $booking
     * @param array $overrides
     * @return void
     * @throws Exception
     */
    public function sendAuthorizationEmail(Booking $booking, array $overrides = []): void
    {
        // Try booking's merchant profile first, then match merchant by name, then fallback to active merchant in DB
        $merchant = $booking->merchantProfile 
            ?: (Merchant::where('name', $booking->merchant)->first() 
            ?: Merchant::where('is_smtp_active', true)->first());

        if (!$merchant) {
            throw new Exception('No active merchant found for SMTP sending.');
        }

        if (!$merchant->is_smtp_active) {
            throw new Exception('SMTP is disabled for merchant: ' . $merchant->name);
        }

        // Save original configuration to restore later
        $originalConfig = config('mail');

        try {
            $port = (int) ($merchant->smtp_port ?: ($merchant->smtp_encryption === 'tls' ? 587 : 465));
            $fromAddress = !empty($merchant->from_email) ? $merchant->from_email : (!empty($overrides['from_email']) ? $overrides['from_email'] : config('mail.from.address'));
            $fromName = 'Reservation Desk';

            // Override SMTP configuration dynamically
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $merchant->smtp_host,
                'mail.mailers.smtp.port' => $port,
                'mail.mailers.smtp.username' => $merchant->smtp_username,
                'mail.mailers.smtp.password' => $merchant->smtp_password,
                'mail.mailers.smtp.encryption' => $merchant->smtp_encryption === 'none' ? null : $merchant->smtp_encryption,
                'mail.from.address' => $fromAddress,
                'mail.from.name' => $fromName,
            ]);

            // Clear resolved mailer instances
            Mail::forgetMailers();

            $recipientEmail = !empty($overrides['email_address']) ? $overrides['email_address'] : $booking->email_address;

            $mailable = new CustomerAuthMail(
                $booking,
                $overrides['subject'] ?? null,
                $fromAddress,
                $fromName,
                $overrides['custom_note'] ?? null,
                $overrides['agent_name'] ?? null,
                $overrides['agent_ext'] ?? null,
                $overrides['email_language'] ?? 'english',
                $overrides['custom_html'] ?? null
            );

            Mail::to($recipientEmail)->send($mailable);

            // Send separate copy of authorization email to the agent as proof of dispatch
            $agentEmail = !empty($overrides['agent_email']) ? $overrides['agent_email'] : null;
            if (!$agentEmail && $booking->agent && filter_var($booking->agent->email, FILTER_VALIDATE_EMAIL)) {
                $agentEmail = $booking->agent->email;
            }
            if (!$agentEmail && auth()->check() && filter_var(auth()->user()->email, FILTER_VALIDATE_EMAIL)) {
                $agentEmail = auth()->user()->email;
            }

            if ($agentEmail) {
                $agentMailable = new CustomerAuthMail(
                    $booking,
                    $overrides['subject'] ?? null,
                    $fromAddress,
                    $fromName,
                    $overrides['custom_note'] ?? null,
                    $overrides['agent_name'] ?? null,
                    $overrides['agent_ext'] ?? null,
                    $overrides['email_language'] ?? 'english',
                    $overrides['custom_html'] ?? null
                );

                Mail::to($agentEmail)->send($agentMailable);
            }

        } catch (Exception $e) {
            Log::error('Dynamic SMTP Mail send failure', [
                'merchant_id' => $merchant->id,
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } finally {
            // Restore original config
            config(['mail' => $originalConfig]);
            Mail::forgetMailers();
        }
    }

    /**
     * Configure mailer dynamically based on Merchant SMTP settings and send the E-Ticket.
     *
     * @param Booking $booking
     * @param string $pdfBinary
     * @return void
     * @throws Exception
     */
    public function sendETicketEmail(Booking $booking, string $pdfBinary, array $overrides = []): void
    {
        $merchant = $booking->merchantProfile 
            ?: (Merchant::where('name', $booking->merchant)->first() 
            ?: Merchant::where('is_smtp_active', true)->first());

        if (!$merchant) {
            throw new Exception('No merchant associated with this booking.');
        }

        if (!$merchant->is_smtp_active) {
            throw new Exception('SMTP is disabled for this merchant.');
        }

        $originalConfig = config('mail');

        try {
            $port = (int) ($merchant->smtp_port ?: ($merchant->smtp_encryption === 'tls' ? 587 : 465));
            $fromAddress = !empty($merchant->from_email) ? $merchant->from_email : (!empty($overrides['from_email']) ? $overrides['from_email'] : config('mail.from.address'));
            $fromName = 'Reservation Desk';
            $recipientEmail = !empty($overrides['email_address']) ? $overrides['email_address'] : $booking->email_address;

            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $merchant->smtp_host,
                'mail.mailers.smtp.port' => $port,
                'mail.mailers.smtp.username' => $merchant->smtp_username,
                'mail.mailers.smtp.password' => $merchant->smtp_password,
                'mail.mailers.smtp.encryption' => $merchant->smtp_encryption === 'none' ? null : $merchant->smtp_encryption,
                'mail.from.address' => $fromAddress,
                'mail.from.name' => $fromName,
            ]);

            Mail::forgetMailers();

            Mail::to($recipientEmail)->send(new \App\Mail\CustomerETicketMail($booking, $pdfBinary, $overrides));

        } catch (Exception $e) {
            Log::error('Dynamic SMTP E-Ticket send failure', [
                'merchant_id' => $merchant->id,
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } finally {
            config(['mail' => $originalConfig]);
            Mail::forgetMailers();
        }
    }
}
