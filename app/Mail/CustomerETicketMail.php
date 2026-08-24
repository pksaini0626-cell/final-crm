<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class CustomerETicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;
    protected string $pdfBinary;
    public array $overrides;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking, string $pdfBinary, array $overrides = [])
    {
        $this->booking = $booking;
        $this->pdfBinary = $pdfBinary;
        $this->overrides = $overrides;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = !empty($this->overrides['subject']) 
            ? $this->overrides['subject'] 
            : 'Your E-Ticket Travel Itinerary - Ref: #' . $this->booking->booking_id;

        $fromEmail = !empty($this->overrides['from_email']) 
            ? $this->overrides['from_email'] 
            : (config('mail.from.address') ?: 'reservation@travelomile.com');

        $fromName = !empty($this->overrides['from_name']) 
            ? $this->overrides['from_name'] 
            : 'Reservation Desk';

        return new Envelope(
            from: new Address($fromEmail, $fromName),
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        if (!empty($this->overrides['custom_html'])) {
            return new Content(
                htmlString: $this->overrides['custom_html'],
            );
        }

        return new Content(
            view: 'emails.customer_e_ticket',
            with: [
                'supportPhone' => $this->overrides['support_phone'] ?? '+1-888-476-0932',
                'customNote' => $this->overrides['custom_note'] ?? null,
                'customerName' => $this->overrides['customer_name'] ?? null,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBinary, 'e-ticket-' . $this->booking->booking_id . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
