<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketingAssignmentMail extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;
    public ?User $assignedBy;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking, ?User $assignedBy = null)
    {
        $this->booking = $booking;
        $this->assignedBy = $assignedBy;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Ticketing Assignment - Booking #' . $this->booking->booking_id . ' (' . ($this->booking->airline_pnr ?: 'PNR Pending') . ')',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ticketing_assignment',
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
