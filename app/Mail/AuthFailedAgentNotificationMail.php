<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuthFailedAgentNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;
    public ?User $cancelledBy;
    public ?string $reason;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking, ?User $cancelledBy = null, ?string $reason = null)
    {
        $this->booking = $booking;
        $this->cancelledBy = $cancelledBy;
        $this->reason = $reason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Customer Authorization & Charge Failed - Booking #' . $this->booking->booking_id . ' (' . ($this->booking->airline_pnr ?: ($this->booking->gk_pnr ?: 'PNR Pending')) . ')',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auth_failed_agent_notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
